<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RoleTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleTemplatePermissionController extends Controller
{
    public function index(): View
    {
        $roleTemplates = RoleTemplate::query()
            ->orderBy('audience')
            ->orderBy('name')
            ->withCount('permissions')
            ->get();

        return view('admin.role_templates.index', [
            'roleTemplates' => $roleTemplates,
        ]);
    }

    public function edit(RoleTemplate $roleTemplate): View
    {
        $roleTemplate->load('permissions');

        $permissions = Permission::query()
            ->orderBy('module_code')
            ->orderBy('resource_code')
            ->orderBy('action')
            ->get();

        $permissionsByModule = $permissions->groupBy('module_code');

        return view('admin.role_templates.edit', [
            'roleTemplate' => $roleTemplate,
            'permissionsByModule' => $permissionsByModule,
            'assignedIds' => $roleTemplate->permissions->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, RoleTemplate $roleTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['permissions'] ?? [])));
        $roleTemplate->permissions()->sync($ids);

        return redirect()
            ->route('admin.role-templates.permissions.edit', $roleTemplate)
            ->with('success', 'Permissions updated for '.$roleTemplate->name.'.');
    }
}
