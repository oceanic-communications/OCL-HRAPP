<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsInductionPolicyFromJson;
use Illuminate\Database\Seeder;

class OceanicProductivityPoliciesSeeder extends Seeder
{
    use SeedsInductionPolicyFromJson;

    private const DATA_FILE = 'data/oceanic_productivity_policies.json';

    public function run(): void
    {
        $this->seedInductionPolicyFromJson();
    }

    protected function dataFile(): string
    {
        return self::DATA_FILE;
    }
}
