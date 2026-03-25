<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@pipeline.com')->first();

        $townships  = ['Dagon', 'Hlaing', 'Kamaryut', 'Mayangone', 'Tarmwe', 'Yankin'];
        $packages   = ['40 Mbps', '100 Mbps', '200 Mbps', '500 Mbps'];
        $plans      = ['Business D/A', 'Business B', 'Home Plus', 'Enterprise'];
        $sources    = ['Own Load', 'Referral', 'Walk-in', 'Online'];
        $bizTypes   = ['Residential', 'Commercial', 'Industrial'];

        for ($i = 1; $i <= 30; $i++) {
            Lead::create([
                'business_name'    => 'Zayar Tun',
                'contact_name'     => 'Zayar Tun / 0925509085',
                'contact_email'    => "zayartun{$i}@example.com",
                'phone'            => '0925509085',
                'township'         => $townships[array_rand($townships)],
                'biz_type'         => $bizTypes[array_rand($bizTypes)],
                'source'           => $sources[array_rand($sources)],
                'weighted'         => '100%',
                'potential'        => 'Yes',
                'package'          => $packages[array_rand($packages)],
                'plan'             => $plans[array_rand($plans)],
                'amount'           => 50000,
                'status'           => 'active',
                'created_by'       => $admin?->id,
            ]);
        }

        $this->command->info('✅ 30 leads seeded.');
    }
}
