<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUserNotification;
use App\Services\Induction\InductionApplicationAuditService;
use App\Support\InductionApplicationAuditEventCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalNotificationController extends Controller
{
    public function __construct(
        private readonly InductionApplicationAuditService $applicationAudit,
    ) {}

    public function markRead(Request $request, PortalUserNotification $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $notification->user_id === $user->id, 403);

        $request->validate([
            'redirect_to' => ['nullable', 'string', 'max:512'],
        ]);

        $notification->markRead();

        $this->applicationAudit->record($user, InductionApplicationAuditEventCode::NOTIFICATION_MARKED_READ, [
            'portal_user_notification_id' => $notification->id,
            'induction_policy_version_id' => $notification->induction_policy_version_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'notification_type' => $notification->type,
            ],
        ]);

        $next = $request->input('redirect_to');
        if (is_string($next) && str_starts_with($next, '/') && ! str_starts_with($next, '//')) {
            return redirect()->to($next);
        }

        $to = $notification->action_url;
        if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//')) {
            return redirect()->to($to);
        }

        return redirect()->route('dashboard');
    }
}
