<?php

namespace Tests\Feature;

use App\Models\InductionEnrollment;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSectionCompletion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionUserProgressTest extends TestCase
{
    #[Test]
    public function admin_induction_index_shows_user_progress(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);

        $employee = User::factory()->create([
            'first_name' => 'Sera',
            'last_name' => 'Naivalurua',
        ]);

        $policy = InductionPolicy::query()->create([
            'name' => 'Test policy',
            'slug' => 'test-policy-progress',
            'is_active' => true,
        ]);

        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $sectionA = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 1,
            'title' => 'Section A',
            'body' => 'Body A',
        ]);

        InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 2,
            'title' => 'Section B',
            'body' => 'Body B',
        ]);

        $enrollment = InductionEnrollment::query()->create([
            'user_id' => $employee->id,
            'induction_policy_version_id' => $version->id,
            'status' => InductionEnrollment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        InductionSectionCompletion::query()->create([
            'induction_enrollment_id' => $enrollment->id,
            'induction_section_id' => $sectionA->id,
            'employee_name_snapshot' => $employee->name,
            'policy_version_label_snapshot' => $version->version_label,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.induction.index'))
            ->assertOk()
            ->assertSee('User induction progress')
            ->assertSee('Sera Naivalurua')
            ->assertSee('1 / 2')
            ->assertSee('In progress');
    }
}
