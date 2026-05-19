<?php

namespace App\Services\Induction;

use App\Models\InductionEnrollment;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSectionCompletion;
use App\Models\User;
use Illuminate\Support\Collection;

final class InductionUserProgressService
{
    public function __construct(
        private readonly InductionFlowService $flowService,
    ) {}

    /**
     * @return array{
     *     version: InductionPolicyVersion|null,
     *     total_sections: int,
     *     rows: Collection<int, array{
     *         user: User,
     *         sections_completed: int,
     *         sections_total: int,
     *         progress_percent: int,
     *         status: string,
     *         started_at: \Illuminate\Support\Carbon|null,
     *         completed_at: \Illuminate\Support\Carbon|null,
     *     }>
     * }
     */
    public function report(): array
    {
        $version = $this->flowService->currentPublishedVersion();
        if ($version === null) {
            return [
                'version' => null,
                'total_sections' => 0,
                'rows' => collect(),
            ];
        }

        $totalSections = $version->activeSections()->count();

        $enrollments = InductionEnrollment::query()
            ->where('induction_policy_version_id', $version->id)
            ->withCount('sectionCompletions')
            ->get()
            ->keyBy('user_id');

        $rows = User::query()
            ->active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (User $user) use ($enrollments, $totalSections): array {
                $enrollment = $enrollments->get($user->id);
                $completed = (int) ($enrollment?->section_completions_count ?? 0);
                $progressPercent = $totalSections > 0
                    ? (int) round(min(100, ($completed / $totalSections) * 100))
                    : 0;

                $status = 'not_started';
                if ($enrollment !== null) {
                    if ($enrollment->isCompleted() || ($totalSections > 0 && $completed >= $totalSections)) {
                        $status = 'completed';
                    } else {
                        $status = 'in_progress';
                    }
                }

                return [
                    'user' => $user,
                    'sections_completed' => $completed,
                    'sections_total' => $totalSections,
                    'progress_percent' => $progressPercent,
                    'status' => $status,
                    'started_at' => $enrollment?->started_at,
                    'completed_at' => $enrollment?->completed_at,
                ];
            });

        return [
            'version' => $version,
            'total_sections' => $totalSections,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     version: InductionPolicyVersion|null,
     *     total_sections: int,
     *     enrollment: InductionEnrollment|null,
     *     summary: array{
     *         user: User,
     *         sections_completed: int,
     *         sections_total: int,
     *         progress_percent: int,
     *         status: string,
     *         started_at: \Illuminate\Support\Carbon|null,
     *         completed_at: \Illuminate\Support\Carbon|null,
     *     },
     *     completions: Collection<int, InductionSectionCompletion>
     * }
     */
    public function detailFor(User $user): array
    {
        $report = $this->report();
        $version = $report['version'];
        $totalSections = $report['total_sections'];

        $summaryRow = $report['rows']->firstWhere(fn (array $row): bool => $row['user']->id === $user->id);
        $summary = $summaryRow ?? [
            'user' => $user,
            'sections_completed' => 0,
            'sections_total' => $totalSections,
            'progress_percent' => 0,
            'status' => 'not_started',
            'started_at' => null,
            'completed_at' => null,
        ];

        $enrollment = $version !== null
            ? InductionEnrollment::query()
                ->where('user_id', $user->id)
                ->where('induction_policy_version_id', $version->id)
                ->first()
            : null;

        $completions = $enrollment !== null
            ? InductionSectionCompletion::query()
                ->where('induction_enrollment_id', $enrollment->id)
                ->with('section')
                ->orderBy('completed_at')
                ->get()
            : collect();

        return [
            'version' => $version,
            'total_sections' => $totalSections,
            'enrollment' => $enrollment,
            'summary' => $summary,
            'completions' => $completions,
        ];
    }
}
