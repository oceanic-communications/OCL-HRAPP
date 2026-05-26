<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OceanicProductivityPoliciesSeeder::class,
            OceanicHrOperationalPoliciesSeeder::class,
        ]);
    }
}
