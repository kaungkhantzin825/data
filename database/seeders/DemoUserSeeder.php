<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $basicPlan = Plan::firstOrCreate(
            ['name' => 'Basic Plan'],
            [
                'staff_limit' => 50,
                'duration_in_days' => 365,
                'description' => 'Basic plan for teams.',
            ]
        );

        // ==============================================================
        // COMPANY A
        // ==============================================================
        $this->command->info('Seeding Company A...');

        $superAdminA = User::firstOrCreate(
            ['email' => 'companyaadmin@gamil.com'],
            [
                'name'              => 'Company A Admin',
                'company_name'      => 'Company A',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'role'              => 'Company Super Admin',
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
            ]
        );
        $superAdminA->refresh();
        $tenantIdA = $superAdminA->tenant_id;

        setPermissionsTeamId($tenantIdA);
        $superAdminRoleA = Role::where(['name' => 'Company Super Admin', 'tenant_id' => $tenantIdA])->first();
        $managerRoleA    = Role::where(['name' => 'Manager', 'tenant_id' => $tenantIdA])->first();
        $userRoleA       = Role::where(['name' => 'User', 'tenant_id' => $tenantIdA])->first();

        if ($superAdminRoleA) $superAdminA->syncRoles([$superAdminRoleA]);

        // Company A can see all organizations
        $orgA1 = Organization::firstOrCreate(['name' => 'Organization A', 'tenant_id' => $tenantIdA]);
        $orgA2 = Organization::firstOrCreate(['name' => 'Organization B', 'tenant_id' => $tenantIdA]);
        $orgA3 = Organization::firstOrCreate(['name' => 'Organization C', 'tenant_id' => $tenantIdA]);
        $superAdminA->organizations()->sync([$orgA1->id, $orgA2->id, $orgA3->id]);

        // Manager 1 → Organization A + Organization B
        $manager1A = User::firstOrCreate(
            ['email' => 'manager1a@gamil.com'],
            [
                'name'              => 'Manager 1 (Company A)',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'tenant_id'         => $tenantIdA,
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
                'created_by'        => $superAdminA->id,
            ]
        );
        if ($managerRoleA) $manager1A->syncRoles([$managerRoleA]);
        $manager1A->organizations()->sync([$orgA1->id, $orgA2->id]);

        // Manager 2 → Organization C
        $manager2A = User::firstOrCreate(
            ['email' => 'manager2a@gamil.com'],
            [
                'name'              => 'Manager 2 (Company A)',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'tenant_id'         => $tenantIdA,
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
                'created_by'        => $superAdminA->id,
            ]
        );
        if ($managerRoleA) $manager2A->syncRoles([$managerRoleA]);
        $manager2A->organizations()->sync([$orgA3->id]);

        // Manager 1's Staff
        // Staff 1 & 2 → Organization A
        // Staff 3 → Organization B
        $m1Staff1 = $this->createStaff('m1staff1@gamil.com', 'M1 Staff 1', $tenantIdA, $orgA1->id, $manager1A->id, $userRoleA, $basicPlan);
        $m1Staff2 = $this->createStaff('m1staff2@gamil.com', 'M1 Staff 2', $tenantIdA, $orgA1->id, $manager1A->id, $userRoleA, $basicPlan);
        $m1Staff3 = $this->createStaff('m1staff3@gamil.com', 'M1 Staff 3', $tenantIdA, $orgA2->id, $manager1A->id, $userRoleA, $basicPlan);

        // Manager 2's Staff
        // Staff 1 & 2 → Organization C
        // Staff 3 → Organization B (shared org)
        $m2Staff1 = $this->createStaff('m2staff1@gamil.com', 'M2 Staff 1', $tenantIdA, $orgA3->id, $manager2A->id, $userRoleA, $basicPlan);
        $m2Staff2 = $this->createStaff('m2staff2@gamil.com', 'M2 Staff 2', $tenantIdA, $orgA3->id, $manager2A->id, $userRoleA, $basicPlan);
        $m2Staff3 = $this->createStaff('m2staff3@gamil.com', 'M2 Staff 3', $tenantIdA, $orgA2->id, $manager2A->id, $userRoleA, $basicPlan);

        // Seed leads in Company A's tenant DB
        $tenantA = Tenant::find($tenantIdA);
        $this->seedLeadsForTenant($tenantA, [
            ['user' => $m1Staff1, 'org_id' => $orgA1->id, 'count' => 5],
            ['user' => $m1Staff2, 'org_id' => $orgA1->id, 'count' => 4],
            ['user' => $m1Staff3, 'org_id' => $orgA2->id, 'count' => 6],
            ['user' => $m2Staff1, 'org_id' => $orgA3->id, 'count' => 5],
            ['user' => $m2Staff2, 'org_id' => $orgA3->id, 'count' => 3],
            ['user' => $m2Staff3, 'org_id' => $orgA2->id, 'count' => 4],
        ]);

        $this->command->info('Company A seeded: 2 Managers, 6 Staff, 3 Organizations, 27 leads.');

        // ==============================================================
        // COMPANY B (fully isolated)
        // ==============================================================
        $this->command->info('Seeding Company B...');

        $superAdminB = User::firstOrCreate(
            ['email' => 'companybadmin@gamil.com'],
            [
                'name'              => 'Company B Admin',
                'company_name'      => 'Company B',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'role'              => 'Company Super Admin',
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
            ]
        );
        $superAdminB->refresh();
        $tenantIdB = $superAdminB->tenant_id;

        setPermissionsTeamId($tenantIdB);
        $superAdminRoleB = Role::where(['name' => 'Company Super Admin', 'tenant_id' => $tenantIdB])->first();
        $managerRoleB    = Role::where(['name' => 'Manager', 'tenant_id' => $tenantIdB])->first();
        $userRoleB       = Role::where(['name' => 'User', 'tenant_id' => $tenantIdB])->first();

        if ($superAdminRoleB) $superAdminB->syncRoles([$superAdminRoleB]);

        // Company B: Organization D + Organization E
        $orgD = Organization::firstOrCreate(['name' => 'Organization D', 'tenant_id' => $tenantIdB]);
        $orgE = Organization::firstOrCreate(['name' => 'Organization E', 'tenant_id' => $tenantIdB]);
        $superAdminB->organizations()->sync([$orgD->id, $orgE->id]);

        // Company B: Manager 1 → Organization D
        $manager1B = User::firstOrCreate(
            ['email' => 'manager1b@gamil.com'],
            [
                'name'              => 'Manager 1 (Company B)',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'tenant_id'         => $tenantIdB,
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
                'created_by'        => $superAdminB->id,
                'manager_id'        => null,
            ]
        );
        if ($managerRoleB) $manager1B->syncRoles([$managerRoleB]);
        $manager1B->organizations()->sync([$orgD->id]);

        // Company B: Manager 2 → Organization E
        $manager2B = User::firstOrCreate(
            ['email' => 'manager2b@gamil.com'],
            [
                'name'              => 'Manager 2 (Company B)',
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'tenant_id'         => $tenantIdB,
                'plan_id'           => $basicPlan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
                'created_by'        => $superAdminB->id,
                'manager_id'        => null,
            ]
        );
        if ($managerRoleB) $manager2B->syncRoles([$managerRoleB]);
        $manager2B->organizations()->sync([$orgE->id]);

        // Manager 1 (B) Staff → Organization D
        $bm1Staff1 = $this->createStaff('bm1staff1@gamil.com', 'B-M1 Staff 1', $tenantIdB, $orgD->id, $manager1B->id, $userRoleB, $basicPlan);
        $bm1Staff2 = $this->createStaff('bm1staff2@gamil.com', 'B-M1 Staff 2', $tenantIdB, $orgD->id, $manager1B->id, $userRoleB, $basicPlan);
        $bm1Staff3 = $this->createStaff('bm1staff3@gamil.com', 'B-M1 Staff 3', $tenantIdB, $orgD->id, $manager1B->id, $userRoleB, $basicPlan);

        // Manager 2 (B) Staff → Organization E
        $bm2Staff1 = $this->createStaff('bm2staff1@gamil.com', 'B-M2 Staff 1', $tenantIdB, $orgE->id, $manager2B->id, $userRoleB, $basicPlan);
        $bm2Staff2 = $this->createStaff('bm2staff2@gamil.com', 'B-M2 Staff 2', $tenantIdB, $orgE->id, $manager2B->id, $userRoleB, $basicPlan);
        $bm2Staff3 = $this->createStaff('bm2staff3@gamil.com', 'B-M2 Staff 3', $tenantIdB, $orgE->id, $manager2B->id, $userRoleB, $basicPlan);

        // Seed leads in Company B's tenant DB
        $tenantB = Tenant::find($tenantIdB);
        $this->seedLeadsForTenant($tenantB, [
            ['user' => $bm1Staff1, 'org_id' => $orgD->id, 'count' => 5],
            ['user' => $bm1Staff2, 'org_id' => $orgD->id, 'count' => 4],
            ['user' => $bm1Staff3, 'org_id' => $orgD->id, 'count' => 6],
            ['user' => $bm2Staff1, 'org_id' => $orgE->id, 'count' => 5],
            ['user' => $bm2Staff2, 'org_id' => $orgE->id, 'count' => 3],
            ['user' => $bm2Staff3, 'org_id' => $orgE->id, 'count' => 4],
        ]);

        $this->command->info('Company B seeded: 2 Managers, 6 Staff, 2 Organizations (D + E), 27 leads.');
        $this->command->newLine();
        $this->command->info('=== TEST ACCOUNTS (password: password) ===');
        $this->command->info('App Admin:         admin@pipeline.com');
        $this->command->info('--- Company A ---');
        $this->command->info('Super Admin A:     companyaadmin@gamil.com');
        $this->command->info('Manager 1 (A):     manager1a@gamil.com  [Org A + Org B]');
        $this->command->info('Manager 2 (A):     manager2a@gamil.com  [Org C]');
        $this->command->info('M1 Staff 1,2:      m1staff1@gamil.com, m1staff2@gamil.com  [Org A]');
        $this->command->info('M1 Staff 3:        m1staff3@gamil.com  [Org B]');
        $this->command->info('M2 Staff 1,2:      m2staff1@gamil.com, m2staff2@gamil.com  [Org C]');
        $this->command->info('M2 Staff 3:        m2staff3@gamil.com  [Org B]');
        $this->command->info('--- Company B ---');
        $this->command->info('Super Admin B:     companybadmin@gamil.com');
        $this->command->info('Manager 1 (B):     manager1b@gamil.com  [Org D]');
        $this->command->info('Manager 2 (B):     manager2b@gamil.com  [Org E]');
        $this->command->info('B-M1 Staff 1-3:    bm1staff1@gamil.com ... bm1staff3@gamil.com  [Org D]');
        $this->command->info('B-M2 Staff 1-3:    bm2staff1@gamil.com ... bm2staff3@gamil.com  [Org E]');
    }

    private function createStaff($email, $name, $tenantId, $orgId, $createdBy, $userRole, $plan): User
    {
        $u = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make('password'),
                'is_admin'          => false,
                'is_active'         => true,
                'tenant_id'         => $tenantId,
                'plan_id'           => $plan->id,
                'plan_expired_at'   => now()->addDays(365)->toDateString(),
                'email_verified_at' => now(),
                'created_by'        => $createdBy,
                'manager_id'        => $createdBy, // Link staff to their manager
            ]
        );
        if ($userRole) $u->syncRoles([$userRole]);
        $u->organizations()->sync([$orgId]);
        return $u;
    }

    private function seedLeadsForTenant(?Tenant $tenant, array $creators): void
    {
        if (!$tenant) return;

        $baseConnection = config('database.connections.mysql_central');
        config(['database.connections.tenant_setup' => array_merge($baseConnection, [
            'database' => $tenant->tenancy_db_name
        ])]);

        $townships    = ['Dagon', 'Hlaing', 'Kamaryut', 'Mayangone', 'Tarmwe'];
        $bizTypes     = ['Residential', 'Commercial', 'Industrial'];
        $packages     = ['40 Mbps', '100 Mbps', '200 Mbps', '500 Mbps'];
        $statuses     = ['New', 'In Progress', 'Closed', 'Follow Up'];
        // Use real plan names that match dashboard summary widgets
        $plans        = [
            'Enterprise DIA', 'Enterprise DIA', // weighted more
            'Business DIA', 'Business DIA',
            'MojoeElite',
            'Premium Home Fiber', 'Premium Home Fiber',
            'Staff Plan',
        ];

        foreach ($creators as $item) {
            $user  = $item['user'];
            $orgId = $item['org_id'];
            $count = $item['count'] ?? 5;

            for ($i = 1; $i <= $count; $i++) {
                // Spread dates across last 90 days so Monthly/Quarterly/Yearly all have data
                $daysAgo = rand(0, 89);
                DB::connection('tenant_setup')->table('leads')->insert([
                    'organization_id' => $orgId,
                    'created_by'      => $user->id,
                    'business_name'   => 'Business ' . $user->name . ' #' . $i,
                    'contact_name'    => 'Contact ' . $i,
                    'phone'           => '09' . rand(100000000, 999999999),
                    'township'        => $townships[array_rand($townships)],
                    'biz_type'        => $bizTypes[array_rand($bizTypes)],
                    'package'         => $packages[array_rand($packages)],
                    'plan'            => $plans[array_rand($plans)],
                    'amount'          => rand(50, 999) * 1000,
                    'status'          => $statuses[array_rand($statuses)],
                    'created_at'      => now()->subDays($daysAgo),
                    'updated_at'      => now(),
                ]);
            }
        }

        DB::purge('tenant_setup');
    }
}
