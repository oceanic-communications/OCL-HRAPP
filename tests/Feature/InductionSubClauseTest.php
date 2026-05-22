<?php

namespace Tests\Feature;

use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSubClause;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionSubClauseTest extends TestCase
{
    #[Test]
    public function admin_can_create_sub_clause_with_auto_sort_order(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $policy = InductionPolicy::query()->create([
            'name' => 'HR policy',
            'abbreviation' => 'HR',
            'slug' => 'hr-policy',
            'is_active' => true,
        ]);
        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);
        $section = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 1,
            'title' => 'Leave',
            'body' => '<p>Intro</p>',
            'requires_signature' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.sub-clauses.store', [$policy, $section]), [
                'title' => 'Annual leave',
                'body' => '<p>Annual leave rules.</p>',
            ])
            ->assertRedirect(route('admin.induction.policies.clauses.show', [$policy, $section]));

        $first = InductionSubClause::query()->where('title', 'Annual leave')->first();
        $this->assertNotNull($first);
        $this->assertSame(1, $first->sort_order);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.sub-clauses.store', [$policy, $section]), [
                'title' => 'Sick leave',
                'body' => '<p>Sick leave rules.</p>',
            ])
            ->assertRedirect(route('admin.induction.policies.clauses.show', [$policy, $section]));

        $second = InductionSubClause::query()->where('title', 'Sick leave')->first();
        $this->assertNotNull($second);
        $this->assertSame(2, $second->sort_order);
    }
}
