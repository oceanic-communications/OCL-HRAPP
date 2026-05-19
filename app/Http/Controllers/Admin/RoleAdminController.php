<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\PortalAccessLevels;
use App\Support\RoleSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleAdminController extends Controller
{
    public function create(): View
    {
        return $this->formView(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $canManageTemplates = $request->user()?->isStaffSuperUser() ?? false;
        $validated = RoleSetup::validate($request, $canManageTemplates);

        $role = DB::transaction(function () use ($request, $validated, $canManageTemplates): Role {
            $template = RoleSetup::resolveTemplate($request, $validated, $canManageTemplates);

            if ($canManageTemplates) {
                RoleSetup::syncTemplatePermissions($template, $validated['permissions'] ?? []);
            }

            return Role::create([
                'name' => $validated['name'],
                'role_template_id' => $template->id,
            ]);
        });

        return redirect()
            ->route('admin.roles.edit', $role)
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

    public function show(Request $request, Role $role): RedirectResponse|View
    {
        $cap = $request->attributes->get('portal_cap');
        if ($request->user()?->isStaffSuperUser() || ($cap?->staffRoleUpdate ?? false)) {
            return redirect()->route('admin.roles.edit', $role);
        }

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

    public function edit(Role $role): View|RedirectResponse
    {
        if ($role->isArchived()) {
            return redirect()
                ->route('admin.roles.index')
                ->withErrors(['archive' => 'Archived roles cannot be edited.']);
        }

        return $this->formView($role);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->isArchived()) {
            abort(404);
        }

        $canManageTemplates = $request->user()?->isStaffSuperUser() ?? false;
        $validated = RoleSetup::validate($request, $canManageTemplates, $role);

        $previousTemplateId = (int) $role->role_template_id;

        DB::transaction(function () use ($request, $validated, $canManageTemplates, $role, $previousTemplateId): void {
            $template = RoleSetup::resolveTemplate($request, $validated, $canManageTemplates, $role);

            if ($canManageTemplates) {
                RoleSetup::syncTemplatePermissions($template, $validated['permissions'] ?? []);
            }

            $role->fill([
                'name' => $validated['name'],
                'role_template_id' => $template->id,
            ]);
            $role->save();

            if ($previousTemplateId !== (int) $template->id || $canManageTemplates) {
                RoleSetup::flushUsersForRole($role);
            }
        });

        return redirect()
            ->route('admin.roles.edit', $role)
            ->with('success', 'Role saved.');
    }

    public function archive(Request $request, Role $role): RedirectResponse
    {
        if ($role->isArchived()) {
            return redirect()->route('admin.roles.index')->with('success', 'Role is already archived.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.edit', $role)
                ->withErrors(['archive' => 'This role is assigned to one or more users. Reassign those users before archiving.']);
        }

        $role->forceFill(['archived_at' => now()])->save();

        return redirect()->route('admin.roles.index')->with('success', 'Role archived.');
    }

    private function formView(?Role $role): View
    {
        if ($role !== null) {
            $role->loadCount('users');
        }

        $context = RoleSetup::formContext($role);

        return view('admin.roles.form', array_merge($context, [
            'role' => $role,
            'isEdit' => $role !== null,
        ]));
    }
}
