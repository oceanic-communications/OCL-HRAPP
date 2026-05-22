<?php

namespace App\Services\Induction;

use App\Models\InductionSectionCompletion;
use App\Models\User;
use Illuminate\Support\Collection;

final class EmployeeAcknowledgementHistoryService
{
    /**
     * All induction section acknowledgements and signatures for an employee, newest first.
     *
     * @return Collection<int, InductionSectionCompletion>
     */
    public function forUser(User $user): Collection
    {
        return InductionSectionCompletion::query()
            ->whereHas('enrollment', fn ($query) => $query->where('user_id', $user->id))
            ->with([
                'section',
                'enrollment.version.policy',
            ])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();
    }
}
