<?php

namespace Tests\Feature;

use App\Models\InductionEnrollment;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSectionCompletion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Support\PortalPermissions;
use Database\Seeders\RbacSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeAcknowledgementHistoryTest extends TestCase
{
    #[Test]
    public function employee_can_view_own_acknowledgement_history(): void
    {
        $employee = User::factory()->create();
        $completion = $this->createCompletionFor($employee, 'Safety policy');

        $this->actingAs($employee)
            ->get(route('portal.acknowledgements'))
            ->assertOk()
            ->assertSee('Acknowledgement history')
            ->assertSee('Safety policy')
            ->assertSee($completion->employee_name_snapshot);
    }

    #[Test]
    public function staff_with_employees_read_can_view_another_employees_history(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = $this->userWithPermissions([PortalPermissions::STAFF_USER_READ]);
        $employee = User::factory()->create();
        $this->createCompletionFor($employee, 'Code of conduct');

        $this->actingAs($admin)
            ->get(route('admin.users.acknowledgements', $employee))
            ->assertOk()
            ->assertSee('Code of conduct')
            ->assertSee($employee->email);
    }

    #[Test]
    public function employee_without_employees_read_cannot_view_another_employees_history(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $this->createCompletionFor($other, 'Hidden section');

        $this->actingAs($viewer)
            ->get(route('admin.users.acknowledgements', $other))
            ->assertForbidden();
    }

    #[Test]
    public function employees_index_shows_renamed_heading_and_history_link(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = $this->userWithPermissions([PortalPermissions::STAFF_USER_READ]);
        $employee = User::factory()->create(['first_name' => 'Alex', 'last_name' => 'Rivera']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Employees')
            ->assertDontSee('User management')
            ->assertSee('History');
    }

    private function createCompletionFor(User $user, string $sectionTitle): InductionSectionCompletion
    {
        $policy = InductionPolicy::query()->create([
            'name' => 'Test policy',
            'abbreviation' => 'TST',
            'slug' => 'test-policy-'.uniqid(),
            'is_active' => true,
        ]);

        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $section = InductionSection::query()->create([
            'induction_policy_version_id' => $version->id,
            'sort_order' => 1,
            'title' => $sectionTitle,
            'body' => 'Body',
            'requires_signature' => true,
        ]);

        $enrollment = InductionEnrollment::query()->create([
            'user_id' => $user->id,
            'induction_policy_version_id' => $version->id,
            'status' => InductionEnrollment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return InductionSectionCompletion::query()->create([
            'induction_enrollment_id' => $enrollment->id,
            'induction_section_id' => $section->id,
            'completed_at' => now(),
            'employee_name_snapshot' => $user->name,
            'policy_version_label_snapshot' => $version->version_label,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'signature_disk' => 'local',
            'signature_path' => 'induction/signatures/test.png',
        ]);
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
