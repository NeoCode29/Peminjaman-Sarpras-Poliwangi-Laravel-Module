<?php

namespace Tests\Unit\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Policies\MarkingPolicy;
use Tests\TestCase;

class MarkingPolicyTest extends TestCase
{
    use DatabaseMigrations;

    private MarkingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->app->make(MarkingPolicy::class);
    }

    private function createUserWithPermission(string|array $permissions = []): User
    {
        $role = Role::factory()->create(['guard_name' => 'web']);

        $perms = collect((array) $permissions)->map(function (string $name) {
            return Permission::factory()->create([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        });

        $user = User::factory()->create();
        $user->assignRole($role);
        $role->syncPermissions($perms->pluck('id')->all());

        return $user;
    }

    private function createMarkingForUser(User $user, array $overrides = []): Marking
    {
        return Marking::create(array_merge([
            'user_id' => $user->id,
            'ukm_id' => null,
            'prasarana_id' => null,
            'lokasi_custom' => 'Lokasi Test',
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(2),
            'jumlah_peserta' => 10,
            'expires_at' => now()->addDays(1),
            'planned_submit_by' => null,
            'status' => Marking::STATUS_ACTIVE,
            'event_name' => 'Event Policy',
            'notes' => null,
        ], $overrides));
    }

    public function test_view_any_allows_manage_or_create_permissions(): void
    {
        $manageUser = $this->createUserWithPermission('marking.manage');
        $createUser = $this->createUserWithPermission('marking.create');
        $noPermUser = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($manageUser));
        $this->assertTrue($this->policy->viewAny($createUser));
        $this->assertFalse($this->policy->viewAny($noPermUser));
    }

    public function test_view_allows_owner_or_manage(): void
    {
        $owner = $this->createUserWithPermission('marking.create');
        $other = $this->createUserWithPermission('marking.manage');
        $stranger = User::factory()->create();

        $marking = $this->createMarkingForUser($owner);

        $this->assertTrue($this->policy->view($owner, $marking));
        $this->assertTrue($this->policy->view($other, $marking));
        $this->assertFalse($this->policy->view($stranger, $marking));
    }

    public function test_create_requires_marking_create_permission(): void
    {
        $userWithCreate = $this->createUserWithPermission('marking.create');
        $userWithout = User::factory()->create();

        $this->assertTrue($this->policy->create($userWithCreate));
        $this->assertFalse($this->policy->create($userWithout));
    }

    public function test_update_delete_extend_convert_allow_owner_active_and_override_permission(): void
    {
        $owner = $this->createUserWithPermission('marking.create');
        $overrideUser = $this->createUserWithPermission('marking.override');
        $stranger = User::factory()->create();

        $activeMarking = $this->createMarkingForUser($owner, [
            'status' => Marking::STATUS_ACTIVE,
        ]);

        $inactiveMarking = $this->createMarkingForUser($owner, [
            'status' => Marking::STATUS_CANCELLED,
        ]);

        // update
        $this->assertTrue($this->policy->update($owner, $activeMarking));
        $this->assertFalse($this->policy->update($owner, $inactiveMarking));
        $this->assertTrue($this->policy->update($overrideUser, $inactiveMarking));
        $this->assertFalse($this->policy->update($stranger, $activeMarking));

        // delete
        $this->assertTrue($this->policy->delete($owner, $activeMarking));
        $this->assertFalse($this->policy->delete($owner, $inactiveMarking));
        $this->assertTrue($this->policy->delete($overrideUser, $inactiveMarking));
        $this->assertFalse($this->policy->delete($stranger, $activeMarking));

        // extend
        $this->assertTrue($this->policy->extend($owner, $activeMarking));
        $this->assertFalse($this->policy->extend($owner, $inactiveMarking));
        $this->assertTrue($this->policy->extend($overrideUser, $inactiveMarking));
        $this->assertFalse($this->policy->extend($stranger, $activeMarking));

        // convert (owner must also satisfy canBeConverted, so we simulate active & not expired)
        $this->assertTrue($this->policy->convert($owner, $activeMarking));
        $this->assertTrue($this->policy->convert($overrideUser, $inactiveMarking));
        $this->assertFalse($this->policy->convert($stranger, $activeMarking));
    }
}
