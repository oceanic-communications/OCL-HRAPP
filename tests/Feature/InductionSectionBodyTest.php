<?php

namespace Tests\Feature;

use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\User;
use App\Support\RichHtmlPurifier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionSectionBodyTest extends TestCase
{
    #[Test]
    public function store_purifies_html_and_rejects_over_word_limit(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $policy = InductionPolicy::query()->create([
            'name' => 'Test policy',
            'abbreviation' => 'TST',
            'slug' => 'test-policy',
            'is_active' => true,
        ]);
        InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $tooManyWords = implode(' ', array_fill(0, InductionSection::BODY_MAX_WORDS + 1, 'word'));

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.store', $policy), [
                'title' => 'Section title',
                'body' => '<p>'.$tooManyWords.'</p>',
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.store', $policy), [
                'title' => 'Section title',
                'body' => '<p>Hello <strong>team</strong></p><script>alert(1)</script>',
            ])
            ->assertRedirect(route('admin.induction.policies.show', $policy));

        $section = InductionSection::query()->where('title', 'Section title')->first();
        $this->assertNotNull($section);
        $this->assertFalse($section->requires_signature);
        $this->assertStringContainsString('<strong>team</strong>', $section->body);
        $this->assertStringNotContainsString('script', $section->body);
        $this->assertSame(
            RichHtmlPurifier::purify('<p>Hello <strong>team</strong></p>'),
            $section->body
        );

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.store', $policy), [
                'title' => 'Signed section',
                'body' => '<p>Sign here</p>',
                'requires_signature' => '1',
            ])
            ->assertRedirect(route('admin.induction.policies.show', $policy));

        $signed = InductionSection::query()->where('title', 'Signed section')->first();
        $this->assertNotNull($signed);
        $this->assertTrue($signed->requires_signature);
    }
}
