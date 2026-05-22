<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    use AuthorizesPortalAccess;

    public function index(): View
    {
        $this->authorizeReadInductionPolicies();

        return view('admin.settings.index');
    }
}
