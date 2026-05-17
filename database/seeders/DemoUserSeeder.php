<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        
        $basicPlan = Plan::firstOrCreate(
            ['name' => 'Basic Plan'],
            [
                'staff_limit' => 5,
                'duration_in_days' => 30,
                'description' => 'Basic plan for small teams.',
            ]
        );

        $proPlan = Plan::firstOrCreate(
            ['name' => 'Pro Plan'],
            [
                'staff_limit' => 20,
                'duration_in_days' => 365,
                'description' => 'Pro plan for growing companies.',
            ]
        );

        // Demo user will be seeded next.
        // Wait, UserObserver creates the tenant and clones the roles when the user is created.

        $demoUser = User::firstOrCreate(
            ['email' => 'demo@pipeline.com'],
            [
                'name'              => 'Demo Company',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'role'              => 'Company Super Admin',
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays($basicPlan->duration_in_days)->toDateString(),
                'email_verified_at' => now(),
            ]
        );
        $demoUser->refresh();
        $demoUserTenantId = $demoUser->tenant_id;
        
        setPermissionsTeamId($demoUserTenantId);
        $superAdminRoleDemo = Role::where(['name' => 'Company Super Admin', 'tenant_id' => $demoUserTenantId])->first();
        if ($superAdminRoleDemo) $demoUser->syncRoles([$superAdminRoleDemo]);

        $demoStaff = User::firstOrCreate(
            ['email' => 'staff@pipeline.com'],
            [
                'name'              => 'Demo Staff',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'role'              => 'Staff',
                'tenant_id'         => $demoUserTenantId,
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays($basicPlan->duration_in_days)->toDateString(),
                'email_verified_at' => now(),
            ]
        );
        $staffRoleDemo = Role::where(['name' => 'Staff', 'tenant_id' => $demoUserTenantId])->first();
        if ($staffRoleDemo) $demoStaff->syncRoles([$staffRoleDemo]);

        $demoUser2 = User::firstOrCreate(
            ['email' => 'company2@pipeline.com'],
            [
                'name'              => 'Second Company',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'role'              => 'Company Super Admin',
                'plan_id'           => $proPlan->id,
                'plan_expired_at'   => now()->addDays($proPlan->duration_in_days)->toDateString(),
                'email_verified_at' => now(),
            ]
        );
        $demoUser2->refresh();
        $demoUser2TenantId = $demoUser2->tenant_id;
        
        setPermissionsTeamId($demoUser2TenantId);
        $superAdminRoleDemo2 = Role::where(['name' => 'Company Super Admin', 'tenant_id' => $demoUser2TenantId])->first();
        if ($superAdminRoleDemo2) $demoUser2->syncRoles([$superAdminRoleDemo2]);

        $this->command->info('Demo users seeded:');
        $this->command->info('  Admin:    admin@pipeline.com / password');
        $this->command->info('  Super Adm:demo@pipeline.com / password (Owns a private DB)');
        $this->command->info('  Staff:    staff@pipeline.com / password (Uses demo@pipeline.com DB)');
        $this->command->info('  Company2: company2@pipeline.com / password (Owns a DIFFERENT private DB)');
    }
}
