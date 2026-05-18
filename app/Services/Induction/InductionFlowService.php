<?php

namespace App\Services\Induction;

use App\Mail\InductionCompletedMail;
use App\Models\InductionEnrollment;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSectionCompletion;
use App\Models\InductionSectionQuestionResponse;
use App\Models\User;
use App\Support\InductionApplicationAuditEventCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class InductionFlowService
{
    public function __construct(
        private InductionPdfService $inductionPdfService,
        private InductionApplicationAuditService $applicationAudit,
    ) {}

    public function currentPublishedVersion(): ?InductionPolicyVersion
    {
        return InductionPolicyVersion::query()
            ->whereHas('policy', fn ($q) => $q->where('is_active', true))
            ->published()
            ->with('policy')
            ->orderByDesc('published_at')
            ->first();
    }

    public function enrollmentFor(User $user, ?string $auditIp = null, ?string $auditUserAgent = null): ?InductionEnrollment
    {
        $version = $this->currentPublishedVersion();
        if ($version === null) {
            return null;
        }

        $enrollment = InductionEnrollment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'induction_policy_version_id' => $version->id,
            ],
            [
                'status' => InductionEnrollment::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]
        );

        if ($enrollment->wasRecentlyCreated) {
            $this->applicationAudit->record($user, InductionApplicationAuditEventCode::ENROLLMENT_CREATED, [
                'induction_policy_id' => $version->induction_policy_id,
                'induction_policy_version_id' => $version->id,
                'induction_enrollment_id' => $enrollment->id,
                'ip_address' => $auditIp,
                'user_agent' => $auditUserAgent,
            ]);
        }

        return $enrollment;
    }

    public function canAccessSection(User $user, InductionSection $section, ?string $auditIp = null, ?string $auditUserAgent = null): bool
    {
        $enrollment = $this->enrollmentFor($user, $auditIp, $auditUserAgent);
        if ($enrollment === null || $enrollment->isCompleted()) {
            return false;
        }

        if ($section->induction_policy_version_id !== $enrollment->induction_policy_version_id) {
            return false;
        }

        $ordered = $section->version->sections()->orderBy('sort_order')->pluck('id');
        $idx = $ordered->search($section->id);
        if ($idx === false) {
            return false;
        }
        if ($idx === 0) {
            return true;
        }

        $prevId = $ordered->get($idx - 1);

        return InductionSectionCompletion::query()
            ->where('induction_enrollment_id', $enrollment->id)
            ->where('induction_section_id', $prevId)
            ->exists();
    }

    public function isSectionCompleted(InductionEnrollment $enrollment, InductionSection $section): bool
    {
        return InductionSectionCompletion::query()
            ->where('induction_enrollment_id', $enrollment->id)
            ->where('induction_section_id', $section->id)
            ->exists();
    }

    /**
     * @param  array{acknowledge: bool, signature_data?: string|null, question_answers?: array<int|string, string>}  $input
     */
    public function completeSection(User $user, InductionSection $section, array $input, ?string $ip, ?string $userAgent): void
    {
        if (! ($input['acknowledge'] ?? false)) {
            throw ValidationException::withMessages([
                'acknowledge' => 'You must confirm that you have read and understood this section and agree to comply with the policies and procedures outlined above.',
            ]);
        }

        $enrollment = $this->enrollmentFor($user, $ip, $userAgent);
        if ($enrollment === null || $enrollment->isCompleted()) {
            throw ValidationException::withMessages([
                'section' => 'Induction is not available or is already completed.',
            ]);
        }

        if (! $this->canAccessSection($user, $section, $ip, $userAgent)) {
            throw ValidationException::withMessages([
                'section' => 'Complete the previous section before continuing.',
            ]);
        }

        if ($this->isSectionCompleted($enrollment, $section)) {
            throw ValidationException::withMessages([
                'section' => 'This section is already completed.',
            ]);
        }

        $sig = $input['signature_data'] ?? null;
        if (! is_string($sig) || ! str_starts_with($sig, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'signature_data' => 'Please sign in the signature box before submitting.',
            ]);
        }

        $section->loadMissing('questions');
        $questionAnswers = is_array($input['question_answers'] ?? null) ? $input['question_answers'] : [];
        foreach ($section->questions as $question) {
            $answer = trim((string) ($questionAnswers[$question->id] ?? $questionAnswers[(string) $question->id] ?? ''));
            if ($answer === '') {
                throw ValidationException::withMessages([
                    "question_answers.{$question->id}" => 'Please answer all section questions before submitting.',
                ]);
            }
        }

        $shouldFinalize = false;

        DB::transaction(function () use ($user, $section, $enrollment, $input, $ip, $userAgent, &$shouldFinalize, $questionAnswers): void {
            $raw = $input['signature_data'];
            $b64 = preg_replace('#^data:image/png;base64,#', '', (string) $raw);
            $binary = base64_decode((string) $b64, true);
            if ($binary === false || strlen($binary) < 40) {
                throw ValidationException::withMessages([
                    'signature_data' => 'Invalid signature image.',
                ]);
            }
            $signaturePath = 'induction/signatures/'.$enrollment->id.'_'.$section->id.'_'.now()->format('YmdHis').'.png';
            Storage::disk('local')->put($signaturePath, $binary);
            $signatureDisk = 'local';

            $completion = InductionSectionCompletion::query()->create([
                'induction_enrollment_id' => $enrollment->id,
                'induction_section_id' => $section->id,
                'completed_at' => now(),
                'employee_name_snapshot' => $user->name,
                'policy_version_label_snapshot' => $section->version->version_label,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'signature_disk' => $signatureDisk,
                'signature_path' => $signaturePath,
            ]);

            foreach ($section->questions as $question) {
                $answer = trim((string) ($questionAnswers[$question->id] ?? $questionAnswers[(string) $question->id] ?? ''));
                InductionSectionQuestionResponse::query()->create([
                    'induction_section_completion_id' => $completion->id,
                    'induction_section_question_id' => $question->id,
                    'response' => $answer,
                ]);
            }

            $this->applicationAudit->record($user, InductionApplicationAuditEventCode::SECTION_ACKNOWLEDGED, [
                'induction_policy_id' => $section->version->induction_policy_id,
                'induction_policy_version_id' => $section->induction_policy_version_id,
                'induction_section_id' => $section->id,
                'induction_enrollment_id' => $enrollment->id,
                'induction_section_completion_id' => $completion->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'payload' => [
                    'requires_signature' => (bool) $section->requires_signature,
                    'digital_signature_stored' => true,
                    'question_count' => $section->questions->count(),
                ],
            ]);

            $total = $section->version->sections()->count();
            $done = InductionSectionCompletion::query()
                ->where('induction_enrollment_id', $enrollment->id)
                ->count();

            if ($done >= $total) {
                $enrollment->forceFill([
                    'status' => InductionEnrollment::STATUS_COMPLETED,
                    'completed_at' => now(),
                ])->save();

                $this->applicationAudit->record($user, InductionApplicationAuditEventCode::PROGRAM_COMPLETED, [
                    'induction_policy_id' => $section->version->induction_policy_id,
                    'induction_policy_version_id' => $section->induction_policy_version_id,
                    'induction_enrollment_id' => $enrollment->id,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);

                $shouldFinalize = true;
            }
        });

        if ($shouldFinalize) {
            $enrollment->refresh()->load(['user', 'version.policy', 'sectionCompletions.section']);
            $this->inductionPdfService->generateAndStoreCompletionPdf($enrollment);

            $hr = config('induction.hr_notification_email');
            Mail::to($enrollment->user->email)->queue(new InductionCompletedMail(
                $enrollment->fresh(['user', 'version.policy', 'sectionCompletions.section']),
                is_string($hr) ? $hr : null,
            ));
        }
    }
}
