<?php

namespace Tests\Feature;

use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\User;
use App\Services\Induction\InductionFlowService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionMultiPolicyTest extends TestCase
{
    #[Test]
    public function my_induction_lists_all_active_published_policies_in_sort_order(): void
    {
        $employee = User::factory()->create();

        $opp = $this->createPolicy('OPP', 'Oceanic Productivity Policies', 'opp-multi', 1);
        $hr = $this->createPolicy('AHOP', 'Oceanic HR Operational Policies', 'hr-multi', 2);

        $this->actingAs($employee)
            ->get(route('portal.induction'))
            ->assertOk()
            ->assertSeeInOrder([
                'Oceanic Productivity Policies',
                'Oceanic HR Operational Policies',
            ])
            ->assertSee('OPP')
            ->assertSee('AHOP');
    }

    #[Test]
    public function second_policy_is_locked_until_first_policy_is_completed(): void
    {
        $employee = User::factory()->create();
        $flow = app(InductionFlowService::class);

        $oppPolicy = $this->createPolicy('OPP', 'OPP Policy', 'opp-lock', 1);
        $hrPolicy = $this->createPolicy('AHOP', 'HR Policy', 'hr-lock', 2);

        $oppSection = InductionSection::query()->create([
            'induction_policy_version_id' => $oppPolicy['version']->id,
            'sort_order' => 1,
            'title' => 'OPP section',
            'body' => 'Body',
        ]);

        $hrSection = InductionSection::query()->create([
            'induction_policy_version_id' => $hrPolicy['version']->id,
            'sort_order' => 1,
            'title' => 'HR section',
            'body' => 'Body',
        ]);

        $this->assertFalse($flow->canAccessPolicy($employee, $hrPolicy['version']));

        $flow->completeSection($employee, $oppSection, ['acknowledge' => true], '127.0.0.1', 'Test');

        $this->assertTrue($flow->canAccessPolicy($employee, $hrPolicy['version']));
        $this->assertTrue($flow->canAccessSection($employee, $hrSection));
    }

    #[Test]
    public function admin_can_reorder_policies(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);

        $opp = InductionPolicy::query()->create([
            'name' => 'OPP',
            'abbreviation' => 'OPP',
            'slug' => 'opp-reorder',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $hr = InductionPolicy::query()->create([
            'name' => 'HR',
            'abbreviation' => 'HR',
            'slug' => 'hr-reorder',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.reorder', $hr), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(1, $hr->fresh()->sort_order);
        $this->assertSame(2, $opp->fresh()->sort_order);
    }

    /**
     * @return array{policy: InductionPolicy, version: InductionPolicyVersion}
     */
    private function createPolicy(string $abbreviation, string $name, string $slug, int $sortOrder): array
    {
        $policy = InductionPolicy::query()->create([
            'name' => $name,
            'abbreviation' => $abbreviation,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        return ['policy' => $policy, 'version' => $version];
    }
}
