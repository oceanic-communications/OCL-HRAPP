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
    public function admin_progress_pages_show_summary_and_acknowledgement_details(): void
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
            'completed_at' => now(),
            'employee_name_snapshot' => $employee->name,
            'policy_version_label_snapshot' => $version->version_label,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.induction.progress.index'))
            ->assertOk()
            ->assertSee('Employee progress')
            ->assertSee('Sera Naivalurua')
            ->assertSee('1 / 2')
            ->assertSee('In progress')
            ->assertSee('View details');

        $this->actingAs($admin)
            ->get(route('admin.induction.progress.show', $employee))
            ->assertOk()
            ->assertSee('Section acknowledgements')
            ->assertSee('Sera Naivalurua')
            ->assertSee('203.0.113.10')
            ->assertSee('Mozilla/5.0 Test Browser')
            ->assertSee('v1');
    }
}
