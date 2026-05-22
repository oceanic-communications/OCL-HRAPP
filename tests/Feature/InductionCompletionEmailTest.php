<?php

namespace Tests\Feature;

use App\Mail\InductionCompletedMail;
use App\Models\InductionEnrollment;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\User;
use App\Services\Induction\InductionFlowService;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionCompletionEmailTest extends TestCase
{
    #[Test]
    public function completing_induction_queues_emails_to_employee_and_hr_with_full_section_pdf(): void
    {
        Mail::fake();
        config(['induction.hr_notification_email' => 'hr@example.com']);

        $employee = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Employee',
            'email' => 'alex@example.com',
        ]);

        $policy = InductionPolicy::query()->create([
            'name' => 'Safety induction',
            'abbreviation' => 'SAF',
            'slug' => 'safety-induction-email',
            'is_active' => true,
        ]);

        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $sectionOne = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 1,
            'title' => 'Welcome',
            'body' => '<p>Read the <strong>welcome</strong> policies.</p>',
            'requires_signature' => false,
        ]);

        $sectionTwo = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 2,
            'title' => 'Sign off',
            'body' => 'Final rules apply to everyone.',
            'requires_signature' => false,
        ]);

        $flow = app(InductionFlowService::class);

        $flow->completeSection($employee, $sectionOne, ['acknowledge' => true], '127.0.0.1', 'TestAgent');
        $flow->completeSection($employee, $sectionTwo, ['acknowledge' => true], '127.0.0.1', 'TestAgent');

        $enrollment = InductionEnrollment::query()
            ->where('user_id', $employee->id)
            ->where('induction_policy_version_id', $version->id)
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertTrue($enrollment->isCompleted());
        $this->assertNotNull($enrollment->completion_pdf_path);

        Mail::assertQueued(InductionCompletedMail::class, 2);

        Mail::assertQueued(InductionCompletedMail::class, function (InductionCompletedMail $mail) use ($employee): bool {
            return $mail->hasTo($employee->email)
                && $mail->recipient === InductionCompletedMail::RECIPIENT_EMPLOYEE;
        });

        Mail::assertQueued(InductionCompletedMail::class, function (InductionCompletedMail $mail): bool {
            return $mail->hasTo('hr@example.com')
                && $mail->recipient === InductionCompletedMail::RECIPIENT_HR;
        });

        $enrollment->load(['user', 'version.policy', 'sectionCompletions.section']);
        $orderedCompletions = $enrollment->sectionCompletions
            ->sortBy(fn ($c) => $c->section->sort_order)
            ->values();

        $html = view('pdf.induction-certificate', [
            'enrollment' => $enrollment,
            'completions' => $orderedCompletions,
        ])->render();

        $this->assertStringContainsString('Read the <strong>welcome</strong> policies.', $html);
        $this->assertStringContainsString('Final rules apply to everyone.', $html);
        $this->assertStringContainsString('Employee sign-off', $html);
        $this->assertStringContainsString('Section 1: Welcome', $html);
        $this->assertStringContainsString('Section 2: Sign off', $html);
    }

    #[Test]
    public function hr_email_is_skipped_when_not_configured(): void
    {
        Mail::fake();
        config(['induction.hr_notification_email' => null]);

        $employee = User::factory()->create();
        $policy = InductionPolicy::query()->create([
            'name' => 'Policy',
            'abbreviation' => 'POL',
            'slug' => 'policy-no-hr',
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
            'title' => 'Only section',
            'body' => 'Content',
        ]);

        app(InductionFlowService::class)->completeSection(
            $employee,
            $section,
            ['acknowledge' => true],
            null,
            null,
        );

        Mail::assertQueued(InductionCompletedMail::class, 1);
        Mail::assertQueued(InductionCompletedMail::class, fn (InductionCompletedMail $mail) => $mail->recipient === InductionCompletedMail::RECIPIENT_EMPLOYEE);
    }
}
