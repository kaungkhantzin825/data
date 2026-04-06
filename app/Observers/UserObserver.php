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

        if ($user->is_active && !$user->is_admin && !$user->tenant_id) {
            $this->provisionTenantDatabase($user);
        }
    }

    public function updated(User $user): void
    {

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

        $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($user->email));
        $dbName = 'pipeline_' . $safeEmail . '_' . $user->id;

        try {

            DB::connection('mysql_central')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );


            $tenantId = (string) Str::uuid();
            DB::connection('mysql_central')->table('tenants')->insert([
                'id' => $tenantId,
                'user_id' => $user->id,
                'tenancy_db_name' => $dbName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            $baseConnection = config('database.connections.mysql_central');
            config([
                'database.connections.tenant_setup' => array_merge($baseConnection, [
                    'database' => $dbName,
                ])
            ]);


            Artisan::call('migrate', [
                '--database' => 'tenant_setup',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);


            DB::purge('tenant_setup');


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
