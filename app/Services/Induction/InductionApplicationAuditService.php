<?php

namespace App\Services\Induction;

use App\Models\InductionApplicationAuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class InductionApplicationAuditService
{
    /**
     * @param  array<string, mixed>  $attributes
     *  Keys: induction_policy_id, induction_policy_version_id, induction_section_id,
     *  induction_enrollment_id, induction_section_completion_id, portal_user_notification_id,
     *  actor_user_id, ip_address, user_agent, correlation_id, payload
     */
    public function record(User $subjectUser, string $eventCode, array $attributes = []): void
    {
        InductionApplicationAuditEvent::query()->create(array_merge([
            'occurred_at' => now(),
            'user_id' => $subjectUser->id,
            'event_code' => $eventCode,
        ], $attributes));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertManyRaw(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($chunk !== []) {
                DB::table('induction_application_audit_events')->insert($chunk);
            }
        }
    }
}
