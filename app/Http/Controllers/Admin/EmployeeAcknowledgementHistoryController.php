<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Induction\EmployeeAcknowledgementHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAcknowledgementHistoryController extends Controller
{
    use AuthorizesPortalAccess;

    public function __construct(
        private readonly EmployeeAcknowledgementHistoryService $historyService,
    ) {}

    public function show(Request $request, User $user): View
    {
        $actor = $request->user();
        abort_unless($actor !== null, 403);

        if ($actor->id !== $user->id) {
            $this->authorizeReadUsers();
        }

        return view('admin.users.acknowledgements', [
            'user' => $user,
            'completions' => $this->historyService->forUser($user),
        ]);
    }
}
