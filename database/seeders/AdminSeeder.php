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
            'manage_tenant_fields' => 'Custom table title (Data Setting)',
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

        // Set global team id to null/0 for global roles
        setPermissionsTeamId(0);

        // Create Roles (Matching User's Image)
        $appAdminRole = Role::firstOrCreate(['name' => 'App Admin', 'guard_name' => 'web', 'tenant_id' => null]);
        $companySuperAdminRole = Role::firstOrCreate(['name' => 'Company Super Admin', 'guard_name' => 'web', 'tenant_id' => null]);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web', 'tenant_id' => null]);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web', 'tenant_id' => null]);

        // App Admin: Plan Menu, Setting, Role, Permission
        $appAdminPermissions = [
            'manage_plans', 'manage_settings', 'setting_profile', 'setting_backup',
            'setting_activity', 'setting_user_status', 'manage_roles', 
            'submenu_roles', 'submenu_permissions'
        ];
        $appAdminRole->syncPermissions(Permission::whereIn('name', $appAdminPermissions)->get());

        // Company Super Admin: Dashboard, List, Create, User Management (User, Role), Data Setting, Setting
        $companySuperAdminPermissions = [
            'view_dashboard', 'action_upload_lead', 'action_download_csv', 'section_lead_detail', 
            'section_product', 'section_other_information',
            'view_users', 'create_users', 'edit_users', 'delete_users', 'submenu_users',
            'manage_roles', 'submenu_roles', 'manage_tenant_fields',
            'manage_settings', 'setting_profile'
        ];
        $companySuperAdminRole->syncPermissions(Permission::whereIn('name', $companySuperAdminPermissions)->get());

        // Manager: Dashboard, List, Create, User Management (User), Data Setting, Setting
        $managerPermissions = [
            'view_dashboard', 'action_upload_lead', 'action_download_csv', 'section_lead_detail', 
            'section_product', 'section_other_information',
            'view_users', 'create_users', 'edit_users', 'delete_users', 'submenu_users',
            'manage_tenant_fields', 'manage_settings', 'setting_profile'
        ];
        $managerRole->syncPermissions(Permission::whereIn('name', $managerPermissions)->get());

        // User: Dashboard, List, Create, Setting
        $userPermissions = [
            'view_dashboard', 'action_upload_lead', 'action_download_csv', 'section_lead_detail', 
            'section_product', 'section_other_information',
            'manage_settings', 'setting_profile'
        ];
        $userRole->syncPermissions(Permission::whereIn('name', $userPermissions)->get());

        $admin = User::firstOrCreate(
            ['email' => 'admin@pipeline.com'],
            [
                'name' => 'Pipeline Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'role' => 'App Admin', // Match new name
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole($appAdminRole);

        $this->command->info('Setup complete: Permissions seeded, roles created and assigned.');
    }
}
