<?php

namespace Tests\Unit\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\PermissionRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    private PermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PermissionRepository::class);
        $this->forgetPermissionCache();
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    #[Test]
    public function it_gets_permissions_with_filters_and_pagination(): void
    {
        Permission::factory()->count(3)->create();
        Permission::factory()->inactive()->count(2)->create();

        Permission::factory()->create([
            'name' => 'inventory.manage_special',
            'display_name' => 'Manage Special Inventory',
            'description' => 'Special inventory management',
            'category' => 'inventory',
            'is_active' => true,
        ]);

        Permission::factory()->inactive()->create([
            'name' => 'inventory.manage_disabled',
            'category' => 'inventory',
        ]);

        $paginator = $this->repository->getAll([
            'search' => 'special',
            'category' => 'inventory',
            'is_active' => true,
        ], perPage: 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('inventory.manage_special', $paginator->items()[0]->name);
        $this->assertTrue($paginator->items()[0]->is_active);
    }

    #[Test]
    public function it_returns_active_permissions_grouped_by_category(): void
    {
        Permission::factory()->create([
            'name' => 'user.view_records',
            'category' => 'user',
        ]);

        Permission::factory()->create([
            'name' => 'report.generate_summary',
            'category' => 'report',
        ]);

        Permission::factory()->inactive()->create([
            'name' => 'system.deprecated_action',
            'category' => 'system',
        ]);

        $grouped = $this->repository->getActiveByCategory();

        $this->assertArrayHasKey('user', $grouped->toArray());
        $this->assertArrayHasKey('report', $grouped->toArray());
        $this->assertArrayNotHasKey('system', $grouped->toArray());
        $this->assertCount(1, $grouped['user']);
        $this->assertSame('user.view_records', $grouped['user']->first()->name);
    }

    #[Test]
    public function it_finds_permission_with_attached_roles(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'role.assign_special',
        ]);

        $role = Role::factory()->create();
        $role->givePermissionTo($permission);

        $found = $this->repository->findById($permission->id);

        $this->assertNotNull($found);
        $this->assertTrue($permission->is($found));
        $this->assertCount(1, $found->roles);
    }

    #[Test]
    public function it_counts_roles_attached_to_permission(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'audit.view_logs',
        ]);

        $roles = Role::factory()->count(2)->create();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        $count = $this->repository->countRoles($permission);

        $this->assertSame(2, $count);
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
