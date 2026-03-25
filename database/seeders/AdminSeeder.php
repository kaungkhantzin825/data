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
            'view_leads' => 'View Leads List',
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
            'manage_tenant_fields' => 'Create Setting Menu',
        ];

        foreach ($permissions as $permName => $menuName) {
            Permission::updateOrCreate(
            ['name' => $permName, 'guard_name' => 'web'],
            ['menu_name' => $menuName]
            );
        }


        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        // $yangonRole = Role::firstOrCreate(['name' => 'yangon', 'guard_name' => 'web']);


        $adminRole->syncPermissions(Permission::all());


        $admin = User::firstOrCreate(
        ['email' => 'admin@pipeline.com'],
        [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]
        );


        $admin->assignRole($adminRole);

        $this->command->info('Setup complete: Permissions seeded, admin role updated.');
    }
}
