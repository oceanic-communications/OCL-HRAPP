<?php

use App\Models\RoleTemplate;
use App\Support\PortalPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $newPermissions = [
            [
                'slug' => PortalPermissions::STAFF_USER_ARCHIVE,
                'module_code' => 'staff_user_management',
                'resource_code' => 'staff_user',
                'action' => 'archive',
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_READ,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'read',
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_CREATE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'create',
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_UPDATE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'update',
            ],
            [
                'slug' => PortalPermissions::INDUCTION_POLICY_ARCHIVE,
                'module_code' => 'induction',
                'resource_code' => 'policy',
                'action' => 'archive',
            ],
            [
                'slug' => PortalPermissions::INDUCTION_ENROLLMENT_READ,
                'module_code' => 'induction',
                'resource_code' => 'enrollment',
                'action' => 'read',
            ],
        ];

        foreach ($newPermissions as $perm) {
            if (DB::table('permissions')->where('slug', $perm['slug'])->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                ...$perm,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $manageTemplateIds = DB::table('permission_role_template')
            ->join('permissions', 'permissions.id', '=', 'permission_role_template.permission_id')
            ->where('permissions.slug', PortalPermissions::INDUCTION_POLICY_MANAGE)
            ->pluck('permission_role_template.role_template_id');

        $granularInductionSlugs = [
            PortalPermissions::INDUCTION_POLICY_READ,
            PortalPermissions::INDUCTION_POLICY_CREATE,
            PortalPermissions::INDUCTION_POLICY_UPDATE,
            PortalPermissions::INDUCTION_POLICY_ARCHIVE,
        ];

        $granularIds = DB::table('permissions')
            ->whereIn('slug', $granularInductionSlugs)
            ->pluck('id', 'slug');

        foreach ($manageTemplateIds as $templateId) {
            foreach ($granularInductionSlugs as $slug) {
                $permId = $granularIds[$slug] ?? null;
                if ($permId === null) {
                    continue;
                }

                $exists = DB::table('permission_role_template')
                    ->where('permission_id', $permId)
                    ->where('role_template_id', $templateId)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role_template')->insert([
                        'permission_id' => $permId,
                        'role_template_id' => $templateId,
                    ]);
                }
            }
        }

        $hrTemplateId = DB::table('role_templates')
            ->where('slug', RoleTemplate::SLUG_HR_ADMIN)
            ->value('id');

        if ($hrTemplateId === null) {
            return;
        }

        $hrGrantSlugs = [
            PortalPermissions::STAFF_USER_ARCHIVE,
            PortalPermissions::INDUCTION_POLICY_READ,
            PortalPermissions::INDUCTION_POLICY_CREATE,
            PortalPermissions::INDUCTION_POLICY_UPDATE,
            PortalPermissions::INDUCTION_POLICY_ARCHIVE,
            PortalPermissions::INDUCTION_ENROLLMENT_READ,
        ];

        $hrPermIds = DB::table('permissions')->whereIn('slug', $hrGrantSlugs)->pluck('id');

        foreach ($hrPermIds as $permId) {
            $exists = DB::table('permission_role_template')
                ->where('permission_id', $permId)
                ->where('role_template_id', $hrTemplateId)
                ->exists();

            if (! $exists) {
                DB::table('permission_role_template')->insert([
                    'permission_id' => $permId,
                    'role_template_id' => $hrTemplateId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            PortalPermissions::STAFF_USER_ARCHIVE,
            PortalPermissions::INDUCTION_POLICY_READ,
            PortalPermissions::INDUCTION_POLICY_CREATE,
            PortalPermissions::INDUCTION_POLICY_UPDATE,
            PortalPermissions::INDUCTION_POLICY_ARCHIVE,
            PortalPermissions::INDUCTION_ENROLLMENT_READ,
        ];

        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        if ($permIds->isNotEmpty()) {
            DB::table('permission_role_template')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }
    }
};
