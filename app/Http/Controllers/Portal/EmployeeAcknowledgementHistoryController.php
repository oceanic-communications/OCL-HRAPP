<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Induction\EmployeeAcknowledgementHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAcknowledgementHistoryController extends Controller
{
    public function __construct(
        private readonly EmployeeAcknowledgementHistoryService $historyService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return view('portal.acknowledgements.index', [
            'user' => $user,
            'completions' => $this->historyService->forUser($user),
        ]);
    }
}
