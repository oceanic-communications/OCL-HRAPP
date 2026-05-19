<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Support\PortalAccessLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleAdminController extends Controller
{
    public function create(): View
    {
        $roleTemplates = RoleTemplate::query()->orderBy('name')->get();

        return view('admin.roles.create', [
            'roleTemplates' => $roleTemplates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'role_template_id' => ['required', 'integer', 'exists:role_templates,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'role_template_id' => $validated['role_template_id'],
        ]);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Role created.');
    }

    public function index(Request $request): View
    {
        $roles = Role::query()
            ->with(['roleTemplate'])
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Role::query()->count(),
            'active' => Role::query()->active()->count(),
            'archived' => Role::query()->whereNotNull('archived_at')->count(),
        ];

        return view('admin.roles.index', [
            'roles' => $roles,
            'stats' => $stats,
        ]);
    }

    public function show(Role $role): View
    {
        $role->load([
            'roleTemplate.permissions',
            'users' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
        ]);

        $permissions = $role->roleTemplate?->permissions ?? collect();

        return view('admin.roles.show', [
            'role' => $role,
            'accessLevels' => PortalAccessLevels::summarize($permissions),
        ]);
    }

    public function edit(Role $role): View
    {
        if ($role->isArchived()) {
            abort(404);
        }

        $role->load('roleTemplate');
        $roleTemplates = RoleTemplate::query()->orderBy('name')->get();

        return view('admin.roles.edit', [
            'role' => $role,
            'roleTemplates' => $roleTemplates,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->isArchived()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'role_template_id' => ['required', 'integer', 'exists:role_templates,id'],
        ]);

        $templateChanged = (int) $role->role_template_id !== (int) $validated['role_template_id'];

        $role->fill([
            'name' => $validated['name'],
            'role_template_id' => $validated['role_template_id'],
        ]);
        $role->save();

        if ($templateChanged) {
            foreach ($role->users()->get() as $user) {
                $user->flushResolvedPermissionSlugs();
            }
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Role updated.');
    }

    public function archive(Request $request, Role $role): RedirectResponse
    {
        if ($role->isArchived()) {
            return redirect()->route('admin.roles.index')->with('success', 'Role is already archived.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.show', $role)
                ->withErrors(['archive' => 'This role is assigned to one or more users. Reassign those users before archiving.']);
        }

        $role->forceFill(['archived_at' => now()])->save();

        return redirect()->route('admin.roles.index')->with('success', 'Role archived.');
    }
}
