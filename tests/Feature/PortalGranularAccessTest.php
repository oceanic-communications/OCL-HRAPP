<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Support\PortalPermissions;
use Database\Seeders\RbacSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalGranularAccessTest extends TestCase
{
    #[Test]
    public function induction_read_only_user_cannot_create_policy(): void
    {
        $user = $this->userWithPermissions([PortalPermissions::INDUCTION_POLICY_READ]);

        $this->actingAs($user)
            ->post(route('admin.induction.policies.store'), ['create_name' => 'Blocked policy'])
            ->assertForbidden();
    }

    #[Test]
    public function induction_enrollment_read_user_sees_progress_not_policy_editor(): void
    {
        $user = $this->userWithPermissions([PortalPermissions::INDUCTION_ENROLLMENT_READ]);

        $this->actingAs($user)
            ->get(route('admin.induction.index'))
            ->assertOk()
            ->assertSee('User induction progress')
            ->assertDontSee('New policy');
    }

    #[Test]
    public function staff_user_archive_permission_allows_archiving_users(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = $this->userWithPermissions([PortalPermissions::STAFF_USER_ARCHIVE, PortalPermissions::STAFF_USER_READ]);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.archive', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNotNull($target->fresh()->archived_at);
    }

    #[Test]
    public function staff_user_without_archive_cannot_archive_users(): void
    {
        $this->seed(RbacSeeder::class);

        $manager = $this->userWithPermissions([
            PortalPermissions::STAFF_USER_READ,
            PortalPermissions::STAFF_USER_UPDATE,
        ]);
        $target = User::factory()->create();

        $this->actingAs($manager)
            ->post(route('admin.users.archive', $target))
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $template = RoleTemplate::query()->create([
            'slug' => 'test_'.uniqid(),
            'name' => 'Test template',
            'audience' => RoleTemplate::AUDIENCE_STAFF,
        ]);

        $permissionIds = collect($slugs)->map(function (string $slug) {
            return Permission::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'module_code' => 'test',
                    'resource_code' => 'test',
                    'action' => 'test',
                ],
            )->id;
        })->all();

        $template->permissions()->sync($permissionIds);

        $role = Role::query()->create([
            'role_template_id' => $template->id,
            'name' => 'Test role',
        ]);

        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $user->flushResolvedPermissionSlugs();

        return $user;
    }
}
