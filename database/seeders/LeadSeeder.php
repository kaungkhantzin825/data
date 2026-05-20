<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        // Disabled lead seeding as requested
        $this->command->info('✅ LeadSeeder execution skipped (disabled).');
    }
}
