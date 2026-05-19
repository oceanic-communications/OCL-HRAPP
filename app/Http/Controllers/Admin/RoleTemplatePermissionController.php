<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RoleTemplate;
use App\Support\PortalAccessLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function create(): View
    {
        return view('admin.role_templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', 'unique:role_templates,slug'],
            'audience' => ['required', 'string', Rule::in([RoleTemplate::AUDIENCE_STAFF])],
        ]);

        $roleTemplate = RoleTemplate::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'audience' => $validated['audience'],
        ]);

        return redirect()
            ->route('admin.role-templates.permissions.edit', $roleTemplate)
            ->with('success', 'Role template created. Configure permissions below.');
    }

    public function edit(RoleTemplate $roleTemplate): View
    {
        $roleTemplate->load('permissions');

        $permissions = Permission::query()
            ->whereIn('slug', PortalAccessLevels::permissionSlugs())
            ->orderBy('module_code')
            ->orderBy('resource_code')
            ->orderBy('action')
            ->get();

        return view('admin.role_templates.edit', [
            'roleTemplate' => $roleTemplate,
            'accessLevels' => PortalAccessLevels::definitions(),
            'permissionIdsBySlug' => PortalAccessLevels::permissionIdsBySlug($permissions),
            'assignedSlugs' => $roleTemplate->permissions->pluck('slug')->all(),
        ]);
    }

    public function update(Request $request, RoleTemplate $roleTemplate): RedirectResponse
    {
        $roleTemplate->loadMissing('permissions');

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $submittedIds = array_values(array_unique(array_map('intval', $validated['permissions'] ?? [])));
        $accessSlugs = PortalAccessLevels::permissionSlugs();
        $preservedIds = $roleTemplate->permissions
            ->reject(fn (Permission $permission) => in_array($permission->slug, $accessSlugs, true))
            ->pluck('id')
            ->all();
        $roleTemplate->permissions()->sync(array_values(array_unique(array_merge($preservedIds, $submittedIds))));

        return redirect()
            ->route('admin.role-templates.permissions.edit', $roleTemplate)
            ->with('success', 'Permissions updated for '.$roleTemplate->name.'.');
    }
}
