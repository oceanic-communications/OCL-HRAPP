<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Induction\InductionUserProgressService;
use Illuminate\View\View;

class InductionEmployeeProgressController extends Controller
{
    use AuthorizesPortalAccess;

    public function __construct(
        private readonly InductionUserProgressService $userProgressService,
    ) {}

    public function index(): View
    {
        $this->authorizeReadInductionEnrollment();

        $inductionProgress = $this->userProgressService->report();

        return view('admin.induction.progress.index', compact('inductionProgress'));
    }

    public function show(User $user): View
    {
        $this->authorizeReadInductionEnrollment();

        $detail = $this->userProgressService->detailFor($user);

        return view('admin.induction.progress.show', compact('user', 'detail'));
    }
}
