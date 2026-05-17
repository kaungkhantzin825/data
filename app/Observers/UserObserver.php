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

            $defaultData = [
                'form_fields' => [
                    'business_name' => ['label' => 'Business Name', 'is_visible' => true],
                    'contact_name' => ['label' => 'Contact Name', 'is_visible' => true],
                    'last_name' => ['label' => 'Last Name', 'is_visible' => true],
                    'contact_email' => ['label' => 'Contact Email', 'is_visible' => true],
                    'phone' => ['label' => 'Phone Number', 'is_visible' => true],
                    'secondary_contact_number' => ['label' => 'Secondary Contact Number', 'is_visible' => true],
                    'biz_type' => ['label' => 'Business Type', 'is_visible' => true],
                    'source' => ['label' => 'Lead Source', 'is_visible' => true],
                    'division' => ['label' => 'Division', 'is_visible' => true],
                    'township' => ['label' => 'Township', 'is_visible' => true],
                    'address' => ['label' => 'Address', 'is_visible' => true],
                    'product' => ['label' => 'Product', 'is_visible' => true],
                    'package' => ['label' => 'Package', 'is_visible' => true],
                    'package_total' => ['label' => 'Package Total', 'is_visible' => true],
                    'discount' => ['label' => 'Discount', 'is_visible' => true],
                    'note' => ['label' => 'Note', 'is_visible' => true],
                    'status' => ['label' => 'Status', 'is_visible' => true],
                    'channel' => ['label' => 'Channel', 'is_visible' => true],
                    'installation_appointment' => ['label' => 'Installation Appointment', 'is_visible' => true],
                    'est_contract_date' => ['label' => 'Est. Contract Date', 'is_visible' => true],
                    'est_start_date' => ['label' => 'Est. Start Date', 'is_visible' => true],
                    'est_follow_up_date' => ['label' => 'Est. Follow Up Date', 'is_visible' => true],
                    'is_referral' => ['label' => 'Referral ?', 'is_visible' => true],
                    'meeting_note' => ['label' => 'Meeting Note', 'is_visible' => true],
                    'next_step' => ['label' => 'Next Step', 'is_visible' => true],
                ]
            ];

            $tenantId = (string) Str::uuid();
            DB::connection('mysql_central')->table('tenants')->insert([
                'id' => $tenantId,
                'user_id' => $user->id,
                'tenancy_db_name' => $dbName,
                'data' => json_encode($defaultData),
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

            // Seed default tenant field options
            $optionsConfig = [
                'biz_type' => ['Residential', 'Commercial', 'Industrial'],
                'source'   => ['Own Load', 'Referral', 'Walk-in', 'Online'],
                'division' => ['Yangon', 'Mandalay', 'Bago'],
                'township' => ['Dagon', 'Hlaing', 'Kamaryut', 'Mayangone'],
                'product'  => ['Internet', 'CCTV', 'IPTV'],
                'channel'  => ['Direct', 'Partner', 'Agent'],
                'package'  => ['40 Mbps', '100 Mbps', '200 Mbps', '500 Mbps'],
            ];

            foreach ($optionsConfig as $fieldName => $values) {
                foreach ($values as $val) {
                    DB::connection('tenant_setup')->table('tenant_field_options')->insert([
                        'tenant_id'    => $user->id,
                        'field_name'   => $fieldName,
                        'option_value' => $val,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            DB::purge('tenant_setup');

            DB::connection('mysql_central')->table('users')->where('id', $user->id)->update([
                'tenant_id' => $tenantId,
            ]);

            // Clone default roles for the new tenant
            $globalRoles = \Spatie\Permission\Models\Role::whereNull('tenant_id')
                ->whereIn('name', ['Company Super Admin', 'Company Admin', 'Staff'])
                ->with('permissions')
                ->get();
            
            $originalTeamId = getPermissionsTeamId();
            setPermissionsTeamId($tenantId);

            foreach ($globalRoles as $globalRole) {
                $newRole = \Spatie\Permission\Models\Role::firstOrCreate([
                    'name' => $globalRole->name,
                    'tenant_id' => $tenantId,
                    'guard_name' => $globalRole->guard_name,
                ]);
                $newRole->syncPermissions($globalRole->permissions);
            }
            
            // Assign the Company Super Admin role to the user who just created the tenant
            $user->refresh();
            $user->syncRoles(['Company Super Admin']);

            setPermissionsTeamId($originalTeamId);

            \Log::info("Tenant database created for user [{$user->email}]: {$dbName}");

        } catch (\Exception $e) {
            \Log::error("Failed to provision tenant database for user [{$user->email}]: " . $e->getMessage());
            throw $e;
        }
    }
}
