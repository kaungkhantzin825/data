<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserObserver
{
    public function created(User $user): void
    {
        // If admin creates a user with is_active = true and no tenant yet,
        // provision their tenant database immediately.
        if ($user->is_active && !$user->is_admin && !$user->tenant_id) {
            $this->provisionTenantDatabase($user);
        }
    }

    public function updated(User $user): void
    {
        // Only trigger when:
        // 1. User is being activated (is_active changed to true)
        // 2. User is NOT the super admin
        // 3. User doesn't already have a tenant database
        if (
            $user->is_active &&
            !$user->is_admin &&
            !$user->tenant_id &&
            $user->wasChanged('is_active')
        ) {
            $this->provisionTenantDatabase($user);
        }
    }

    private function provisionTenantDatabase(User $user): void
    {
        // Build a safe database name from the user email
        // e.g. demo@pipeline.com => tenant_demo_pipeline_com_5
        $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($user->email));
        $dbName = 'tenant_' . $safeEmail . '_' . $user->id;

        try {
            // 1. Create the new MySQL database using explicit central connection
            DB::connection('mysql_central')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            // 2. Record the tenant in the central tenants table
            $tenantId = (string) Str::uuid();
            DB::connection('mysql_central')->table('tenants')->insert([
                'id'              => $tenantId,
                'user_id'         => $user->id,
                'tenancy_db_name' => $dbName,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // 3. Configure a temporary connection pointing to the new tenant DB
            $baseConnection = config('database.connections.mysql_central');
            config([
                'database.connections.tenant_setup' => array_merge($baseConnection, [
                    'database' => $dbName,
                ])
            ]);

            // 4. Run tenant-specific migrations on the new database
            Artisan::call('migrate', [
                '--database' => 'tenant_setup',
                '--path'     => 'database/migrations/tenant',
                '--force'    => true,
            ]);

            // 5. Purge the temporary connection
            DB::purge('tenant_setup');

            // 6. Link the user to their new tenant record
            DB::connection('mysql_central')->table('users')->where('id', $user->id)->update([
                'tenant_id' => $tenantId,
            ]);

            \Log::info("Tenant database created for user [{$user->email}]: {$dbName}");

        } catch (\Exception $e) {
            \Log::error("Failed to provision tenant database for user [{$user->email}]: " . $e->getMessage());
            throw $e;
        }
    }
}
