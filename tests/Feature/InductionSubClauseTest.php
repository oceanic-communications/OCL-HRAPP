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
    public function admin_can_open_sub_clause_create_form_on_clause_page(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $policy = InductionPolicy::query()->create([
            'name' => 'HR policy',
            'abbreviation' => 'HR',
            'slug' => 'hr-policy-create-form',
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
            ->get(route('admin.induction.policies.clauses.show', [$policy, $section]))
            ->assertOk()
            ->assertSee('Add sub-clause', false)
            ->assertSee('Create sub-clause', false);
    }

    #[Test]
    public function admin_can_create_sub_clause_when_version_is_not_yet_published(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $policy = InductionPolicy::query()->create([
            'name' => 'Draft policy',
            'abbreviation' => 'DRF',
            'slug' => 'draft-policy-sub-clause',
            'is_active' => true,
        ]);
        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'Draft',
            'published_at' => null,
        ]);
        $section = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 1,
            'title' => 'Intro',
            'body' => '<p>Intro</p>',
            'requires_signature' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.sub-clauses.store', [$policy, $section]), [
                'title' => 'First sub-clause',
                'body' => '<p>Content</p>',
            ])
            ->assertRedirect(route('admin.induction.policies.clauses.show', [$policy, $section]));

        $this->assertNotNull($version->fresh()->published_at);
    }

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
