<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePortalController extends Controller
{
    /**
     * @var array<string, array{title: string, intro: string}>
     */
    private const PAGE_COPY = [
        'probation' => [
            'title' => 'Probation',
            'intro' => 'Review cycles, feedback, and probation milestones.',
        ],
        'training' => [
            'title' => 'Training',
            'intro' => 'Assigned courses, completions, and learning history.',
        ],
        'training.assign' => [
            'title' => 'Assign training',
            'intro' => 'Allocate courses and due dates to your team.',
        ],
        'training.approvals' => [
            'title' => 'Training completion approvals',
            'intro' => 'Review and approve evidence of course completion.',
        ],
        'leave' => [
            'title' => 'Leave & attendance',
            'intro' => 'Apply for leave, view balances, and attendance records.',
        ],
        'documents' => [
            'title' => 'My documents',
            'intro' => 'Contracts, payslips, certificates, and personal HR documents.',
        ],
        'conduct.notices' => [
            'title' => 'My notices',
            'intro' => 'Formal notices and letters requiring your attention.',
        ],
        'conduct.improvement-plans' => [
            'title' => 'Improvement plans',
            'intro' => 'Active performance improvement plans and check-ins.',
        ],
        'conduct.meetings' => [
            'title' => 'Meeting records',
            'intro' => 'Notes and outcomes from HR or disciplinary meetings.',
        ],
        'conduct.acknowledge' => [
            'title' => 'Acknowledge actions',
            'intro' => 'Items that require your acknowledgement or signature.',
        ],
        'conduct.response' => [
            'title' => 'Submit response',
            'intro' => 'Provide written responses or appeals as required.',
        ],
        'approvals' => [
            'title' => 'Manager approvals',
            'intro' => 'Leave, overtime, and other requests awaiting your decision.',
        ],
        'blockouts' => [
            'title' => 'Leave blockouts',
            'intro' => 'Blackout periods and team coverage rules.',
        ],
        'conduct.team-concerns' => [
            'title' => 'Team concerns',
            'intro' => 'Conduct matters raised for employees you manage.',
        ],
        'conduct.create-incident' => [
            'title' => 'Create incident',
            'intro' => 'Log a new conduct or performance incident.',
        ],
        'conduct.pending-actions' => [
            'title' => 'Pending actions',
            'intro' => 'Follow-ups and tasks assigned to you as a manager.',
        ],
        'conduct.escalations' => [
            'title' => 'Escalation tracker',
            'intro' => 'Track escalations and handoffs to HR.',
        ],
        'organization' => [
            'title' => 'Organization',
            'intro' => 'Structure, positions, and reporting lines.',
        ],
        'performance' => [
            'title' => 'Performance management',
            'intro' => 'Goals, reviews, and performance workflows.',
        ],
        'appraisals' => [
            'title' => 'Appraisals',
            'intro' => 'Appraisal cycles, forms, and calibration.',
        ],
        'reports' => [
            'title' => 'HR reports',
            'intro' => 'Workforce analytics and operational HR reports.',
        ],
        'conduct.investigations' => [
            'title' => 'Investigation dashboard',
            'intro' => 'Case load, status, and investigator assignments.',
        ],
        'conduct.analytics' => [
            'title' => 'Disciplinary analytics',
            'intro' => 'Trends, volumes, and outcomes across conduct cases.',
        ],
        'settings' => [
            'title' => 'Settings',
            'intro' => 'Notification preferences, security, and profile details.',
        ],
    ];

    public function page(Request $request): View
    {
        $key = $request->route('portalPage');
        abort_unless(is_string($key) && isset(self::PAGE_COPY[$key]), 404);

        $copy = self::PAGE_COPY[$key];

        return view('portal.generic', [
            'portalPageTitle' => $copy['title'],
            'portalPageIntro' => $copy['intro'],
        ]);
    }
}
