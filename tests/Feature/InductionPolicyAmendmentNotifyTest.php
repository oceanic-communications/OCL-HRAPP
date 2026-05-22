<?php

namespace Tests\Feature;

use App\Mail\InductionPolicyChangedMail;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\PortalUserNotification;
use App\Models\User;
use App\Support\InductionAcknowledgementMode;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionPolicyAmendmentNotifyTest extends TestCase
{
    #[Test]
    public function clause_amendment_with_staff_repeat_sends_granular_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $employee = User::factory()->create(['email' => 'amend@example.com']);

        $policy = InductionPolicy::query()->create([
            'name' => 'OHS Policy',
            'abbreviation' => 'OHS',
            'slug' => 'ohs-amend-notify',
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
            'title' => 'Safety rules',
            'body' => '<p>Original</p>',
            'requires_signature' => false,
            'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.induction.policies.clauses.update', [$policy, $section]), [
                'title' => 'Safety rules',
                'body' => '<p>Updated text</p>',
                'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
                'staff_must_repeat_induction' => '1',
            ])
            ->assertRedirect(route('admin.induction.policies.clauses.show', [$policy, $section]));

        $notification = PortalUserNotification::query()
            ->where('user_id', $employee->id)
            ->where('type', PortalUserNotification::TYPE_INDUCTION_REPEAT)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('OHS · Amendment · Clause · Safety rules', $notification->title);
        $this->assertStringContainsString('Change: Amendment clause — "Safety rules".', $notification->body);
        $this->assertStringContainsString('complete induction again', $notification->body);

        Mail::assertQueued(InductionPolicyChangedMail::class, function (InductionPolicyChangedMail $mail): bool {
            return $mail->changeNotification->changeTypeLabel() === 'Amendment'
                && $mail->requiresRepeat === true;
        });
    }
}
