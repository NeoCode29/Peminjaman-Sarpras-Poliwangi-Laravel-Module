<?php

namespace Tests\Unit\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(RoleRepository::class);
        $this->forgetPermissionCache();
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    #[Test]
    public function it_gets_roles_with_filters_and_pagination(): void
    {
        Role::factory()->count(2)->create();
        Role::factory()->inactive()->create([
            'name' => 'archived_role',
            'display_name' => 'Archived Role',
            'category' => 'archive',
        ]);

        Role::factory()->create([
            'name' => 'inventory_manager',
            'display_name' => 'Inventory Manager',
            'description' => 'Manage inventory',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        Role::factory()->create([
            'name' => 'inventory_auditor',
            'display_name' => 'Inventory Auditor',
            'description' => 'Audit inventory records',
            'category' => 'inventory',
            'guard_name' => 'api',
            'is_active' => true,
        ]);

        $paginator = $this->repository->getAll([
            'search' => 'Inventory',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ], perPage: 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('inventory_manager', $paginator->items()[0]->name);
        $this->assertTrue($paginator->items()[0]->is_active);
        $this->assertSame('web', $paginator->items()[0]->guard_name);
    }

    #[Test]
    public function it_returns_active_roles_ordered_by_display_name(): void
    {
        Role::factory()->create([
            'name' => 'b_manager',
            'display_name' => 'B Manager',
        ]);

        Role::factory()->create([
            'name' => 'a_supervisor',
            'display_name' => 'A Supervisor',
        ]);

        Role::factory()->inactive()->create([
            'name' => 'inactive_role',
            'display_name' => 'Inactive Role',
        ]);

        $activeRoles = $this->repository->getActive();

        $this->assertCount(2, $activeRoles);
        $this->assertEquals(['A Supervisor', 'B Manager'], $activeRoles->pluck('display_name')->all());
    }

    #[Test]
    public function it_finds_role_with_permissions_and_users(): void
    {
        $role = Role::factory()->create([
            'name' => 'compliance_officer',
        ]);

        $permissions = Permission::factory()->count(2)->create();
        $role->syncPermissions($permissions->pluck('id'));

        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $found = $this->repository->findById($role->id);

        $this->assertNotNull($found);
        $this->assertTrue($role->is($found));
        $this->assertEqualsCanonicalizing($permissions->pluck('id')->all(), $found->permissions->pluck('id')->all());
        $this->assertCount(2, $found->users);
    }

    #[Test]
    public function it_counts_users_attached_to_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'auditor',
        ]);

        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $count = $this->repository->countUsers($role);

        $this->assertSame(3, $count);
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
