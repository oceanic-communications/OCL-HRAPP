<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RoleTemplate;
use App\Support\PortalPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_user')->delete();
        DB::table('roles')->delete();
        DB::table('permission_role_template')->delete();
        DB::table('role_templates')->delete();
        DB::table('permissions')->delete();

        $permissionIdsBySlug = $this->seedPermissions();
        $templateIdsBySlug = $this->seedRoleTemplates();
        $this->seedTemplatePermissions($permissionIdsBySlug, $templateIdsBySlug);
        $this->seedRoles($templateIdsBySlug);
    }

    /**
     * @return array<string, int>
     */
    private function seedPermissions(): array
    {
        $now = now();
        $rows = [
            [
                'slug' => PortalPermissions::STAFF_USER_READ,
                'module_code' => 'staff_user_management',
                'resource_code' => 'staff_user',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::STAFF_USER_CREATE,
                'module_code' => 'staff_user_management',
                'resource_code' => 'staff_user',
                'action' => 'create',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::STAFF_USER_UPDATE,
                'module_code' => 'staff_user_management',
                'resource_code' => 'staff_user',
                'action' => 'update',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::STAFF_USER_ARCHIVE,
                'module_code' => 'staff_user_management',
                'resource_code' => 'staff_user',
                'action' => 'archive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_MANAGE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'manage',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_READ,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_CREATE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'create',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_UPDATE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'update',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_ARCHIVE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'archive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_ENROLLMENT_READ,
                'module_code' => 'induction',
                'resource_code' => 'enrollment',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::INDUCTION_CHANGE_LOG_READ,
                'module_code' => 'induction',
                'resource_code' => 'change_log',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::STAFF_ROLE_READ,
                'module_code' => 'staff_role_management',
                'resource_code' => 'staff_role',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => PortalPermissions::STAFF_ROLE_UPDATE,
                'module_code' => 'staff_role_management',
                'resource_code' => 'staff_role',
                'action' => 'update',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('permissions')->insert($rows);

        return Permission::query()->pluck('id', 'slug')->all();
    }

    /**
     * @return array<string, int>
     */
    private function seedRoleTemplates(): array
    {
        $now = now();
        $templates = [
            [
                'slug' => RoleTemplate::SLUG_EMPLOYEE,
                'name' => 'Employee',
                'audience' => RoleTemplate::AUDIENCE_STAFF,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => RoleTemplate::SLUG_MANAGER,
                'name' => 'Manager',
                'audience' => RoleTemplate::AUDIENCE_STAFF,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => RoleTemplate::SLUG_HR_ADMIN,
                'name' => 'HR/Admin',
                'audience' => RoleTemplate::AUDIENCE_STAFF,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => RoleTemplate::SLUG_DIRECTOR_GM,
                'name' => 'Director/GM',
                'audience' => RoleTemplate::AUDIENCE_STAFF,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('role_templates')->insert($templates);

        return RoleTemplate::query()->pluck('id', 'slug')->all();
    }

    /**
     * @param  array<string, int>  $permissionIdsBySlug
     * @param  array<string, int>  $templateIdsBySlug
     */
    private function seedTemplatePermissions(array $permissionIdsBySlug, array $templateIdsBySlug): void
    {
        $pivotRows = [];

        $attach = function (string $templateSlug, array $permissionSlugs) use (&$pivotRows, $permissionIdsBySlug, $templateIdsBySlug): void {
            $tid = $templateIdsBySlug[$templateSlug] ?? null;
            if ($tid === null) {
                return;
            }
            foreach ($permissionSlugs as $permSlug) {
                $pid = $permissionIdsBySlug[$permSlug] ?? null;
                if ($pid === null) {
                    continue;
                }
                $pivotRows[] = [
                    'permission_id' => $pid,
                    'role_template_id' => $tid,
                ];
            }
        };

        $attach(RoleTemplate::SLUG_EMPLOYEE, [
            PortalPermissions::STAFF_USER_READ,
        ]);

        $attach(RoleTemplate::SLUG_MANAGER, [
            PortalPermissions::STAFF_USER_READ,
            PortalPermissions::STAFF_USER_CREATE,
            PortalPermissions::STAFF_USER_UPDATE,
        ]);

        $attach(RoleTemplate::SLUG_HR_ADMIN, [
            PortalPermissions::STAFF_USER_READ,
            PortalPermissions::STAFF_USER_CREATE,
            PortalPermissions::STAFF_USER_UPDATE,
            PortalPermissions::STAFF_USER_ARCHIVE,
            PortalPermissions::STAFF_ROLE_READ,
            PortalPermissions::STAFF_ROLE_UPDATE,
            PortalPermissions::INDUCTION_POLICY_MANAGE,
            PortalPermissions::INDUCTION_POLICY_READ,
            PortalPermissions::INDUCTION_POLICY_CREATE,
            PortalPermissions::INDUCTION_POLICY_UPDATE,
            PortalPermissions::INDUCTION_POLICY_ARCHIVE,
            PortalPermissions::INDUCTION_ENROLLMENT_READ,
        ]);

        $attach(RoleTemplate::SLUG_DIRECTOR_GM, [
            PortalPermissions::STAFF_USER_READ,
        ]);

        DB::table('permission_role_template')->insert($pivotRows);
    }

    /**
     * @param  array<string, int>  $templateIdsBySlug
     */
    private function seedRoles(array $templateIdsBySlug): void
    {
        $now = now();
        DB::table('roles')->insert([
            [
                'role_template_id' => $templateIdsBySlug[RoleTemplate::SLUG_EMPLOYEE],
                'name' => 'Employee',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_template_id' => $templateIdsBySlug[RoleTemplate::SLUG_MANAGER],
                'name' => 'Manager',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_template_id' => $templateIdsBySlug[RoleTemplate::SLUG_HR_ADMIN],
                'name' => 'HR/Admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_template_id' => $templateIdsBySlug[RoleTemplate::SLUG_DIRECTOR_GM],
                'name' => 'Director/GM',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
