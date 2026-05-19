<?php

use App\Models\RoleTemplate;
use App\Support\PortalPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateId = DB::table('role_templates')
            ->where('slug', RoleTemplate::SLUG_HR_ADMIN)
            ->value('id');

        if ($templateId === null) {
            return;
        }

        foreach ([
            [
                'slug' => PortalPermissions::STAFF_ROLE_READ,
                'module_code' => 'staff_role_management',
                'resource_code' => 'staff_role',
                'action' => 'read',
            ],
            [
                'slug' => PortalPermissions::STAFF_ROLE_UPDATE,
                'module_code' => 'staff_role_management',
                'resource_code' => 'staff_role',
                'action' => 'update',
            ],
        ] as $perm) {
            $permId = DB::table('permissions')->where('slug', $perm['slug'])->value('id');

            if ($permId === null) {
                $now = now();
                $permId = DB::table('permissions')->insertGetId([
                    'slug' => $perm['slug'],
                    'module_code' => $perm['module_code'],
                    'resource_code' => $perm['resource_code'],
                    'action' => $perm['action'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
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

    public function down(): void
    {
        $templateId = DB::table('role_templates')
            ->where('slug', RoleTemplate::SLUG_HR_ADMIN)
            ->value('id');

        if ($templateId === null) {
            return;
        }

        $permIds = DB::table('permissions')
            ->whereIn('slug', [
                PortalPermissions::STAFF_ROLE_READ,
                PortalPermissions::STAFF_ROLE_UPDATE,
            ])
            ->pluck('id');

        if ($permIds->isEmpty()) {
            return;
        }

        DB::table('permission_role_template')
            ->where('role_template_id', $templateId)
            ->whereIn('permission_id', $permIds)
            ->delete();
    }
};
