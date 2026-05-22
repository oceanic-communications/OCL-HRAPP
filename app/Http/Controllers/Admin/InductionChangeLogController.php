<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\InductionChangeLog;
use App\Models\InductionPolicy;
use App\Support\InductionChangeLogPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InductionChangeLogController extends Controller
{
    use AuthorizesPortalAccess;

    public function index(Request $request): View
    {
        $this->authorizeReadInductionChangeLogs();

        $policyId = $request->integer('policy');
        $policy = null;
        if ($policyId > 0) {
            $policy = InductionPolicy::query()->find($policyId);
        }

        $logs = InductionChangeLog::query()
            ->with(['actor', 'policy'])
            ->when($policy !== null, fn ($q) => $q->where('induction_policy_id', $policy->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $policies = InductionPolicy::query()->orderBy('name')->get(['id', 'name', 'abbreviation']);

        return view('admin.induction.change-logs.index', [
            'logs' => $logs,
            'policies' => $policies,
            'filterPolicy' => $policy,
        ]);
    }

    public function show(InductionChangeLog $change_log): View
    {
        $this->authorizeReadInductionChangeLogs();

        $change_log->load(['actor', 'policy', 'version']);

        return view('admin.induction.change-logs.show', [
            'log' => $change_log,
            'presenter' => new InductionChangeLogPresenter($change_log),
        ]);
    }
}
