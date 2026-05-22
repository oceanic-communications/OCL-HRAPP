<?php

use App\Models\RoleTemplate;
use App\Support\PortalPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $slug = PortalPermissions::INDUCTION_CHANGE_LOG_READ;
        $permId = DB::table('permissions')->where('slug', $slug)->value('id');

        if ($permId === null) {
            $now = now();
            $permId = DB::table('permissions')->insertGetId([
                'slug' => $slug,
                'module_code' => 'induction',
                'resource_code' => 'change_log',
                'action' => 'read',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $templateId = DB::table('role_templates')
            ->where('slug', RoleTemplate::SLUG_HR_ADMIN)
            ->value('id');

        if ($templateId === null) {
            return;
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

    public function down(): void
    {
        $permId = DB::table('permissions')
            ->where('slug', PortalPermissions::INDUCTION_CHANGE_LOG_READ)
            ->value('id');
        $templateId = DB::table('role_templates')
            ->where('slug', RoleTemplate::SLUG_HR_ADMIN)
            ->value('id');

        if ($permId === null || $templateId === null) {
            return;
        }

        DB::table('permission_role_template')
            ->where('permission_id', $permId)
            ->where('role_template_id', $templateId)
            ->delete();

        DB::table('permissions')->where('id', $permId)->delete();
    }
};
