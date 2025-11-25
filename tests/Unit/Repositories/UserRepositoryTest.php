<?php

namespace Tests\Unit\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }

    #[Test]
    public function it_creates_and_finds_user_by_id_email_and_username(): void
    {
        $user = $this->repository->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'user_type' => 'mahasiswa',
            'status' => 'active',
        ]);

        $foundById = $this->repository->findById($user->id);
        $foundByEmail = $this->repository->findByEmail('test@example.com');
        $foundByUsername = $this->repository->findByUsername('testuser');

        $this->assertNotNull($foundById);
        $this->assertTrue($user->is($foundById));
        $this->assertTrue($user->is($foundByEmail));
        $this->assertTrue($user->is($foundByUsername));
    }

    #[Test]
    public function it_updates_user_and_returns_fresh_with_relations(): void
    {
        $role = Role::factory()->create();

        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'olduser',
            'email' => 'old@example.com',
            'role_id' => $role->id,
        ]);

        $updated = $this->repository->update($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('new@example.com', $updated->email);
    }

    #[Test]
    public function it_gets_all_users_with_filters_and_pagination(): void
    {
        $role = Role::factory()->create();

        User::factory()->create([
            'name' => 'Active Student',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => $role->id,
        ]);

        User::factory()->create([
            'name' => 'Inactive Staff',
            'user_type' => 'staff',
            'status' => 'inactive',
            'role_id' => $role->id,
        ]);

        $paginator = $this->repository->getAll([
            'status' => 'active',
            'user_type' => 'mahasiswa',
        ], perPage: 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('Active Student', $paginator->items()[0]->name);
    }

    #[Test]
    public function it_gets_active_users_with_filters(): void
    {
        $role = Role::factory()->create();

        User::factory()->create([
            'name' => 'Active Student',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => $role->id,
        ]);

        User::factory()->create([
            'name' => 'Inactive Student',
            'user_type' => 'mahasiswa',
            'status' => 'inactive',
            'role_id' => $role->id,
        ]);

        $active = $this->repository->getActive([
            'user_type' => 'mahasiswa',
        ]);

        $this->assertCount(1, $active);
        $this->assertSame('Active Student', $active->first()->name);
    }

    #[Test]
    public function it_blocks_and_unblocks_user_correctly(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'blocked_until' => null,
            'blocked_reason' => null,
        ]);

        $blockedUntil = now()->addDay()->toDateTimeString();
        $reason = 'Testing block';

        $blocked = $this->repository->block($user, $blockedUntil, $reason);

        $this->assertSame('blocked', $blocked->status);
        $this->assertSame($reason, $blocked->blocked_reason);
        $this->assertNotNull($blocked->blocked_until);

        $unblocked = $this->repository->unblock($blocked);

        $this->assertSame('active', $unblocked->status);
        $this->assertNull($unblocked->blocked_until);
        $this->assertNull($unblocked->blocked_reason);
    }
}
