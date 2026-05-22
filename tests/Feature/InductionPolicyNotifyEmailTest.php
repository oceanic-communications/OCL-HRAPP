<?php

namespace Tests\Feature;

use App\Mail\InductionPolicyChangedMail;
use App\Mail\PortalAccessGrantedMail;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\PortalUserNotification;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Support\InductionAcknowledgementMode;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionPolicyNotifyEmailTest extends TestCase
{
    #[Test]
    public function creating_clause_with_notify_queues_emails_and_portal_notifications(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $employee = User::factory()->create(['email' => 'staff@example.com']);

        $policy = InductionPolicy::query()->create([
            'name' => 'HR Policy',
            'abbreviation' => 'HR',
            'slug' => 'hr-notify-test',
            'is_active' => true,
            'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
        ]);

        InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.store', $policy), [
                'title' => 'New clause',
                'body' => '<p>Updated rules</p>',
                'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
                'notify_employees' => '1',
            ])
            ->assertRedirect(route('admin.induction.policies.show', $policy));

        $notification = PortalUserNotification::query()
            ->where('user_id', $employee->id)
            ->where('type', PortalUserNotification::TYPE_INDUCTION_POLICY_CHANGED)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('HR · New · Clause · New clause', $notification->title);
        $this->assertStringContainsString('Policy: HR Policy (HR).', $notification->body);
        $this->assertStringContainsString('Change: New clause — "New clause".', $notification->body);

        Mail::assertQueued(InductionPolicyChangedMail::class, function (InductionPolicyChangedMail $mail) use ($employee): bool {
            return $mail->hasTo($employee->email)
                && $mail->changeNotification->changeTypeLabel() === 'New'
                && $mail->changeNotification->levelLabel() === 'Clause'
                && $mail->changeNotification->clauseTitle === 'New clause';
        });
    }

    #[Test]
    public function creating_user_queues_portal_access_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $template = RoleTemplate::query()->create([
            'name' => 'Staff template',
            'slug' => 'staff-notify-template',
            'audience' => RoleTemplate::AUDIENCE_STAFF,
        ]);
        $role = Role::query()->create([
            'role_template_id' => $template->id,
            'name' => 'Staff',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'first_name' => 'New',
                'last_name' => 'Employee',
                'email' => 'new.employee@example.com',
                'role_id' => $role->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        Mail::assertQueued(PortalAccessGrantedMail::class, function (PortalAccessGrantedMail $mail): bool {
            return $mail->hasTo('new.employee@example.com');
        });
    }

    #[Test]
    public function clause_requires_acknowledgement_mode_on_create(): void
    {
        $admin = User::factory()->create(['is_staff_super_user' => true]);
        $policy = InductionPolicy::query()->create([
            'name' => 'Policy',
            'abbreviation' => 'POL',
            'slug' => 'policy-mode-required',
            'is_active' => true,
        ]);
        InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.induction.policies.clauses.store', $policy), [
                'title' => 'Clause',
                'body' => '<p>Text</p>',
            ])
            ->assertSessionHasErrors('acknowledgement_mode');
    }
}
