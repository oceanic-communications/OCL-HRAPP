<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    use AuthorizesPortalAccess;

    public function index(Request $request): View
    {
        $this->authorizeReadUsers();
        $users = User::query()
            ->with(['roles.roleTemplate'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->active()->count(),
            'archived' => User::query()->whereNotNull('archived_at')->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorizeCreateUsers();

        $rolesForCreate = Role::query()->active()->with('roleTemplate')->orderBy('name')->get();

        return view('admin.users.create', [
            'rolesForCreate' => $rolesForCreate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreateUsers();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::query()->with('roleTemplate')->findOrFail((int) $validated['role_id']);

        $user = User::create([
            'title' => $validated['title'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(64)),
            'is_staff_super_user' => false,
        ]);

        $user->roles()->sync([$role->id]);
        $user->flushResolvedPermissionSlugs();

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorizeUpdateUsers();

        $roleOptions = Role::query()->active()->with('roleTemplate')->orderBy('name')->get();
        $user = $user->loadMissing('roles.roleTemplate');

        $selectedRoleId = old('role_id', $user->roles->first()?->id);

        return view('admin.users.edit', [
            'user' => $user,
            'roleOptions' => $roleOptions,
            'selectedRoleId' => $selectedRoleId !== null && $selectedRoleId !== '' ? (int) $selectedRoleId : null,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUpdateUsers();

        if ($user->is_staff_super_user && ! $request->user()?->isStaffSuperUser()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => [
                Rule::requiredIf(fn () => ! $user->is_staff_super_user),
                'nullable',
                'integer',
                'exists:roles,id',
            ],
        ]);

        $user->title = $validated['title'] ?? null;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];

        if ($user->is_staff_super_user) {
            $user->roles()->detach();
            $user->flushResolvedPermissionSlugs();
            $user->save();

            return redirect()->route('admin.users.index')->with('success', 'User updated.');
        }

        $role = Role::query()->with('roleTemplate')->findOrFail((int) $validated['role_id']);
        $user->roles()->sync([$role->id]);
        $user->flushResolvedPermissionSlugs();
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function archive(Request $request, User $user): RedirectResponse
    {
        $this->authorizeArchiveUsers();

        if ($user->is_staff_super_user) {
            abort(403, 'Super admin accounts cannot be archived.');
        }

        if ($user->isArchived()) {
            return redirect()->route('admin.users.index')->with('success', 'User is already archived.');
        }

        $user->forceFill(['archived_at' => now()])->save();

        return redirect()->route('admin.users.index')->with('success', 'User archived.');
    }
}
