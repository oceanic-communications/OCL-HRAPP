<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $dashboardNotifications = $user
            ? $user->portalNotifications()->orderByDesc('created_at')->limit(25)->get()
            : collect();

        return view('portal.dashboard', compact('dashboardNotifications'));
    }
}
