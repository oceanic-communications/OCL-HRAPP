<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class RoleSetup
{
    /**
     * @return array{
     *     accessLevels: list<array>,
     *     permissionIdsBySlug: array<string, int>,
     *     assignedSlugs: list<string>,
     *     roleTemplates: Collection<int, RoleTemplate>,
     *     canManageTemplates: bool,
     * }
     */
    public static function formContext(?Role $role = null): array
    {
        $role?->loadMissing('roleTemplate.permissions');

        $permissions = Permission::query()
            ->whereIn('slug', PortalAccessLevels::permissionSlugs())
            ->orderBy('module_code')
            ->orderBy('resource_code')
            ->orderBy('action')
            ->get();

        $assignedSlugs = $role?->roleTemplate?->permissions->pluck('slug')->all() ?? [];

        return [
            'accessLevels' => PortalAccessLevels::definitions(),
            'permissionIdsBySlug' => PortalAccessLevels::permissionIdsBySlug($permissions),
            'assignedSlugs' => $assignedSlugs,
            'roleTemplates' => RoleTemplate::query()->orderBy('name')->get(),
            'canManageTemplates' => auth()->user()?->isStaffSuperUser() ?? false,
            'templateSharedRoleCount' => $role?->roleTemplate
                ? Role::query()->where('role_template_id', $role->role_template_id)->count()
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validate(Request $request, bool $canManageTemplates, ?Role $role = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:128'],
            'template_mode' => [
                Rule::requiredIf($canManageTemplates),
                'nullable',
                Rule::in(['existing', 'new']),
            ],
            'role_template_id' => [
                Rule::requiredIf(fn () => ! $canManageTemplates || $request->input('template_mode') === 'existing'),
                'nullable',
                'integer',
                'exists:role_templates,id',
            ],
            'template_name' => [
                Rule::requiredIf(fn () => $canManageTemplates && $request->input('template_mode') === 'new'),
                'nullable',
                'string',
                'max:128',
            ],
            'template_slug' => [
                Rule::requiredIf(fn () => $canManageTemplates && $request->input('template_mode') === 'new'),
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('role_templates', 'slug'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];

        if ($canManageTemplates && $request->input('template_mode') === 'new' && $role?->role_template_id) {
            $rules['template_slug'][] = Rule::unique('role_templates', 'slug')->ignore($role->role_template_id);
        }

        return $request->validate($rules);
    }

    public static function resolveTemplate(Request $request, array $validated, bool $canManageTemplates, ?Role $role = null): RoleTemplate
    {
        if (! $canManageTemplates) {
            return RoleTemplate::query()->findOrFail((int) $validated['role_template_id']);
        }

        if (($validated['template_mode'] ?? 'existing') === 'new') {
            $template = $role?->roleTemplate ?? new RoleTemplate;

            $template->fill([
                'name' => $validated['template_name'],
                'slug' => $validated['template_slug'],
                'audience' => RoleTemplate::AUDIENCE_STAFF,
            ]);
            $template->save();

            return $template;
        }

        return RoleTemplate::query()->findOrFail((int) $validated['role_template_id']);
    }

    public static function syncTemplatePermissions(RoleTemplate $template, array $permissionIds): void
    {
        $template->loadMissing('permissions');

        $submittedIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $accessSlugs = PortalAccessLevels::permissionSlugs();
        $preservedIds = $template->permissions
            ->reject(fn (Permission $permission) => in_array($permission->slug, $accessSlugs, true))
            ->pluck('id')
            ->all();

        $template->permissions()->sync(array_values(array_unique(array_merge($preservedIds, $submittedIds))));
    }

    public static function flushUsersForRole(Role $role): void
    {
        foreach ($role->users()->get() as $user) {
            $user->flushResolvedPermissionSlugs();
        }
    }
}
