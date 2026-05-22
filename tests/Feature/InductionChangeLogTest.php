<?php

namespace Tests\Feature;

use App\Models\InductionChangeLog;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Support\InductionAcknowledgementMode;
use App\Support\PortalPermissions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionChangeLogTest extends TestCase
{
    #[Test]
    public function user_without_permission_cannot_view_change_log(): void
    {
        $user = $this->userWithPermissions([PortalPermissions::INDUCTION_POLICY_READ]);

        $this->actingAs($user)
            ->get(route('admin.induction.change-logs.index'))
            ->assertForbidden();
    }

    #[Test]
    public function change_log_records_from_to_values_actor_and_timestamp(): void
    {
        $admin = User::factory()->create([
            'is_staff_super_user' => true,
            'first_name' => 'Audit',
            'last_name' => 'Admin',
        ]);

        $policy = InductionPolicy::query()->create([
            'name' => 'Original name',
            'abbreviation' => 'ORG',
            'slug' => 'org-policy-log',
            'is_active' => true,
            'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
        ]);
        InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'v1',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.induction.policies.update', $policy), [
                'policy' => [
                    $policy->id => [
                        'name' => 'Updated name',
                        'abbreviation' => 'ORG',
                        'is_active' => '1',
                    ],
                ],
                'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
            ])
            ->assertRedirect(route('admin.induction.policies.show', $policy));

        $log = InductionChangeLog::query()
            ->where('action', 'induction_policy.updated')
            ->where('induction_policy_id', $policy->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->actor_user_id);
        $this->assertNotNull($log->created_at);
        $this->assertIsArray($log->changes);

        $nameChange = collect($log->changes)->firstWhere('field', 'name');
        $this->assertNotNull($nameChange);
        $this->assertSame('Original name', $nameChange['from']);
        $this->assertSame('Updated name', $nameChange['to']);
    }

    #[Test]
    public function change_log_is_immutable(): void
    {
        $log = InductionChangeLog::query()->create([
            'actor_user_id' => User::factory()->create()->id,
            'action' => 'test.action',
            'changes' => [['field' => 'x', 'label' => 'X', 'from' => 'a', 'to' => 'b']],
        ]);

        $this->assertFalse($log->update(['action' => 'tampered']));
        $this->assertDatabaseMissing('induction_change_logs', ['id' => $log->id, 'action' => 'tampered']);
    }

    #[Test]
    public function authorized_user_can_view_change_log_detail(): void
    {
        $viewer = $this->userWithPermissions([PortalPermissions::INDUCTION_CHANGE_LOG_READ]);
        $actor = User::factory()->create(['first_name' => 'Log', 'last_name' => 'Author']);
        $policy = InductionPolicy::query()->create([
            'name' => 'Test',
            'abbreviation' => 'TST',
            'slug' => 'tst-log-view',
            'is_active' => true,
        ]);

        $log = InductionChangeLog::query()->create([
            'actor_user_id' => $actor->id,
            'action' => 'induction_section.created',
            'subject_type' => InductionSection::class,
            'subject_id' => 1,
            'induction_policy_id' => $policy->id,
            'metadata' => ['subject_label' => 'Clause: Safety'],
            'changes' => [
                ['field' => 'title', 'label' => 'Clause title', 'from' => null, 'to' => 'Safety'],
            ],
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.induction.change-logs.show', $log))
            ->assertOk()
            ->assertSee('Log Author', false)
            ->assertSee('Safety', false)
            ->assertSee('From', false)
            ->assertSee('To', false);
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
