<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {

        $permissions = [
            'view_dashboard' => 'Dashboard View',
            'action_upload_lead' => 'Upload Lead',
            'action_download_csv' => 'Download CSV',
            'section_lead_detail' => 'Form: Lead Detail',
            'section_product' => 'Form: Product',
            'section_other_information' => 'Form: Other Information',
            'view_users' => 'View Users',
            'create_users' => 'Create Users',
            'edit_users' => 'Edit Users',
            'delete_users' => 'Delete Users',
            'manage_roles' => 'Manage Roles',
            'manage_settings' => 'Manage Settings',
            'setting_profile' => 'Profile Settings',
            'setting_backup' => 'Database Backup',
            'setting_activity' => 'Activity Log',
            'setting_user_status' => 'User Login Status',
            'manage_plans' => 'Plan Menu',
            'manage_tenant_fields' => 'Custom table title',
            'submenu_users' => 'Users Menu (User Management)',
            'submenu_roles' => 'Roles Menu (User Management)',
            'submenu_permissions' => 'Permissions Menu (User Management)',
        ];

        foreach ($permissions as $permName => $menuName) {
            Permission::updateOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
                ['menu_name' => $menuName]
            );
        }

        // Set global team id to 0 for global roles
        setPermissionsTeamId(0);

        // Create Roles
        $piplineAdminRole = Role::firstOrCreate(['name' => 'Pipline Admin', 'guard_name' => 'web', 'tenant_id' => null]);
        $companySuperAdminRole = Role::firstOrCreate(['name' => 'Company Super Admin', 'guard_name' => 'web', 'tenant_id' => null]);
        $companyAdminRole = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'web', 'tenant_id' => null]);
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web', 'tenant_id' => null]);

        // Pipline Admin Permissions (Excludes Dashboard, Lead actions, Forms, and Create Setting Menu)
        $piplineAdminPermissions = [
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'manage_roles', 'manage_settings', 'setting_profile', 'setting_backup',
            'setting_activity', 'setting_user_status', 'manage_plans',
            'submenu_users', 'submenu_roles', 'submenu_permissions'
        ];
        $piplineAdminRole->syncPermissions(Permission::whereIn('name', $piplineAdminPermissions)->get());

        // Staff, Company Admin, and Company Super Admin have all permissions EXCEPT manage_plans
        $tenantPermissions = Permission::where('name', '!=', 'manage_plans')->get();
        $staffRole->syncPermissions($tenantPermissions);
        $companyAdminRole->syncPermissions($tenantPermissions);
        $companySuperAdminRole->syncPermissions($tenantPermissions);

        $admin = User::firstOrCreate(
            ['email' => 'admin@pipeline.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'role' => 'Pipline Admin', // Assign to Pipline Admin by default
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole($piplineAdminRole);

        $this->command->info('Setup complete: Permissions seeded, roles created and assigned.');
    }
}
