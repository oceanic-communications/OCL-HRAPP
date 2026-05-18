<?php

namespace App\Services\Induction;

use App\Models\InductionChangeLog;
use App\Models\InductionEnrollment;
use App\Models\InductionPolicyVersion;
use App\Models\PortalUserNotification;
use App\Models\User;
use App\Support\InductionApplicationAuditEventCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class InductionPolicyAdminChangeService
{
    public function __construct(
        private readonly InductionApplicationAuditService $applicationAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array{ip?: string|null, user_agent?: string|null, correlation_id?: string|null}  $complianceContext
     */
    public function record(
        User $actor,
        string $action,
        ?string $subjectType,
        ?int $subjectId,
        ?int $policyId,
        ?int $versionId,
        array $metadata,
        bool $staffRepeatRequested,
        ?InductionPolicyVersion $versionForRepeat = null,
        array $complianceContext = [],
    ): void {
        $versionForRepeat ??= $this->resolveVersion($versionId);
        $staffRepeatApplied = false;
        $ip = $complianceContext['ip'] ?? null;
        $ua = $complianceContext['user_agent'] ?? null;
        $correlationId = $complianceContext['correlation_id'] ?? null;

        if ($staffRepeatRequested && $versionForRepeat !== null && $versionForRepeat->published_at !== null) {
            $resetCount = $this->resetEnrollmentsForVersion($versionForRepeat, $actor, $correlationId, $ip, $ua);
            $notifCount = $this->notifyAllActiveUsers($versionForRepeat, $actor, $correlationId, $ip, $ua);
            $staffRepeatApplied = true;
            $metadata['compliance_enrollments_progress_reset_count'] = $resetCount;
            $metadata['compliance_repeat_notifications_assigned_count'] = $notifCount;
        }

        InductionChangeLog::query()->create([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'induction_policy_id' => $policyId,
            'induction_policy_version_id' => $versionId,
            'metadata' => $metadata === [] ? null : $metadata,
            'staff_repeat_requested' => $staffRepeatRequested,
            'staff_repeat_applied' => $staffRepeatApplied,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'correlation_id' => $correlationId,
        ]);
    }

    private function resolveVersion(?int $versionId): ?InductionPolicyVersion
    {
        if ($versionId === null) {
            return null;
        }

        return InductionPolicyVersion::query()->find($versionId);
    }

    private function resetEnrollmentsForVersion(
        InductionPolicyVersion $version,
        User $actor,
        ?string $correlationId,
        ?string $ip,
        ?string $ua,
    ): int {
        $count = 0;
        InductionEnrollment::query()
            ->where('induction_policy_version_id', $version->id)
            ->with('user')
            ->orderBy('id')
            ->each(function (InductionEnrollment $enrollment) use ($version, $actor, $correlationId, $ip, $ua, &$count): void {
                $enrollment->sectionCompletions()->delete();

                if ($enrollment->completion_pdf_disk && $enrollment->completion_pdf_path) {
                    Storage::disk($enrollment->completion_pdf_disk)->delete($enrollment->completion_pdf_path);
                }

                $enrollment->forceFill([
                    'status' => InductionEnrollment::STATUS_IN_PROGRESS,
                    'completed_at' => null,
                    'completion_pdf_disk' => null,
                    'completion_pdf_path' => null,
                ])->save();

                $user = $enrollment->user;
                if ($user !== null) {
                    $this->applicationAudit->record($user, InductionApplicationAuditEventCode::ENROLLMENT_PROGRESS_RESET, [
                        'induction_policy_id' => $version->induction_policy_id,
                        'induction_policy_version_id' => $version->id,
                        'induction_enrollment_id' => $enrollment->id,
                        'actor_user_id' => $actor->id,
                        'ip_address' => $ip,
                        'user_agent' => $ua,
                        'correlation_id' => $correlationId,
                        'payload' => [
                            'reason' => 'staff_repeat_induction',
                            'admin_user_id' => $actor->id,
                        ],
                    ]);
                }
                $count++;
            });

        return $count;
    }

    private function notifyAllActiveUsers(
        InductionPolicyVersion $version,
        User $actor,
        ?string $correlationId,
        ?string $ip,
        ?string $ua,
    ): int {
        $version->loadMissing('policy');
        $policyName = $version->policy?->name ?? 'Induction';

        $title = 'Induction needs your attention';
        $body = sprintf(
            '%s was updated. Please open Induction and work through the required sections again.',
            $policyName,
        );

        $actionUrl = route('portal.induction', [], false);
        $total = 0;

        User::query()
            ->active()
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($version, $title, $body, $actionUrl, $actor, $correlationId, $ip, $ua, &$total): void {
                $now = now()->toDateTimeString();
                $notifRows = [];
                $auditRows = [];
                foreach ($users as $user) {
                    $notifRows[] = [
                        'user_id' => $user->id,
                        'type' => PortalUserNotification::TYPE_INDUCTION_REPEAT,
                        'title' => $title,
                        'body' => $body,
                        'action_url' => $actionUrl,
                        'induction_policy_version_id' => $version->id,
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $auditRows[] = [
                        'occurred_at' => $now,
                        'user_id' => $user->id,
                        'event_code' => InductionApplicationAuditEventCode::NOTIFICATION_REPEAT_ASSIGNED,
                        'induction_policy_id' => $version->induction_policy_id,
                        'induction_policy_version_id' => $version->id,
                        'induction_section_id' => null,
                        'induction_enrollment_id' => null,
                        'induction_section_completion_id' => null,
                        'portal_user_notification_id' => null,
                        'actor_user_id' => $actor->id,
                        'ip_address' => $ip,
                        'user_agent' => $ua,
                        'correlation_id' => $correlationId,
                        'payload' => json_encode([
                            'notification_type' => PortalUserNotification::TYPE_INDUCTION_REPEAT,
                            'issued_by_user_id' => $actor->id,
                        ]),
                    ];
                    $total++;
                }
                if ($notifRows !== []) {
                    DB::table('portal_user_notifications')->insert($notifRows);
                }
                if ($auditRows !== []) {
                    $this->applicationAudit->insertManyRaw($auditRows);
                }
            });

        return $total;
    }
}
