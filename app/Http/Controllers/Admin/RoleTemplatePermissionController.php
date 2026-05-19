<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
/**
 * Legacy routes — role templates are managed via the unified role form.
 */
class RoleTemplatePermissionController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.roles.index');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.roles.create');
    }

    public function edit(RoleTemplate $roleTemplate): RedirectResponse
    {
        $role = Role::query()
            ->where('role_template_id', $roleTemplate->id)
            ->active()
            ->orderBy('id')
            ->first();

        if ($role !== null) {
            return redirect()->route('admin.roles.edit', $role);
        }

        return redirect()
            ->route('admin.roles.create')
            ->withErrors(['role_template_id' => 'No active role uses this template. Create a role and link it to this template, or pick another template.']);
    }

    public function update(Request $request, RoleTemplate $roleTemplate): RedirectResponse
    {
        return $this->edit($roleTemplate);
    }
}
