<?php

namespace Tests\Unit\Services;

use App\Models\GlobalApprover;
use App\Models\Role;
use App\Models\User;
use App\Repositories\GlobalApproverRepository;
use App\Repositories\Interfaces\GlobalApproverRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Services\GlobalApproverService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GlobalApproverServiceTest extends TestCase
{
    use RefreshDatabase;

    private GlobalApproverService $service;
    private GlobalApproverRepositoryInterface $globalApproverRepository;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globalApproverRepository = new GlobalApproverRepository();
        $this->userRepository = new UserRepository();

        $this->service = new GlobalApproverService(
            $this->globalApproverRepository,
            $this->userRepository,
            app(DatabaseManager::class)
        );
    }

    // ==========================================
    // getGlobalApprovers Tests
    // ==========================================

    public function test_get_global_approvers_returns_paginated_results(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 2]);

        $result = $this->service->getGlobalApprovers();

        $this->assertCount(2, $result);
    }

    public function test_get_global_approvers_with_filters(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'is_active' => false]);

        $result = $this->service->getGlobalApprovers(['is_active' => true]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_active);
    }

    // ==========================================
    // getActiveApprovers Tests
    // ==========================================

    public function test_get_active_approvers_returns_only_active(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'is_active' => false]);

        $result = $this->service->getActiveApprovers();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_active);
    }

    // ==========================================
    // getGlobalApproverById Tests
    // ==========================================

    public function test_get_global_approver_by_id_returns_approver(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->service->getGlobalApproverById($approver->id);

        $this->assertEquals($approver->id, $result->id);
    }

    public function test_get_global_approver_by_id_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->getGlobalApproverById(999);
    }

    // ==========================================
    // createGlobalApprover Tests
    // ==========================================

    public function test_create_global_approver_creates_successfully(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $result = $this->service->createGlobalApprover([
            'user_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(GlobalApprover::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(1, $result->approval_level);
        $this->assertTrue($result->is_active);
    }

    public function test_create_global_approver_throws_exception_when_user_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User tidak ditemukan.');

        $this->service->createGlobalApprover([
            'user_id' => 999,
            'approval_level' => 1,
        ]);
    }

    public function test_create_global_approver_throws_exception_when_user_not_active(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User tidak aktif.');

        $this->service->createGlobalApprover([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);
    }

    public function test_create_global_approver_throws_exception_when_combination_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User sudah menjadi global approver dengan level yang sama.');

        $this->service->createGlobalApprover([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);
    }

    public function test_create_global_approver_allows_same_user_different_level(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);

        $result = $this->service->createGlobalApprover([
            'user_id' => $user->id,
            'approval_level' => 2,
        ]);

        $this->assertEquals(2, $result->approval_level);
    }

    // ==========================================
    // updateGlobalApprover Tests
    // ==========================================

    public function test_update_global_approver_updates_successfully(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $result = $this->service->updateGlobalApprover($approver, [
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $this->assertEquals(2, $result->approval_level);
        $this->assertFalse($result->is_active);
    }

    public function test_update_global_approver_throws_exception_when_level_combination_exists(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User1 has level 1
        $approver1 = GlobalApprover::factory()->create([
            'user_id' => $user1->id,
            'approval_level' => 1,
        ]);

        // User1 also has level 2
        GlobalApprover::factory()->create([
            'user_id' => $user1->id,
            'approval_level' => 2,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User sudah menjadi global approver dengan level yang sama.');

        // Try to change level 1 to level 2 (which already exists)
        $this->service->updateGlobalApprover($approver1, [
            'approval_level' => 2,
        ]);
    }

    public function test_update_global_approver_allows_same_level(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Update with same level should work
        $result = $this->service->updateGlobalApprover($approver, [
            'approval_level' => 1,
            'is_active' => false,
        ]);

        $this->assertEquals(1, $result->approval_level);
        $this->assertFalse($result->is_active);
    }

    // ==========================================
    // deleteGlobalApprover Tests
    // ==========================================

    public function test_delete_global_approver_deletes_successfully(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create(['user_id' => $user->id]);
        $approverId = $approver->id;

        $this->service->deleteGlobalApprover($approver);

        $this->assertDatabaseMissing('global_approvers', ['id' => $approverId]);
    }

    // ==========================================
    // toggleActive Tests
    // ==========================================

    public function test_toggle_active_toggles_status(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $result = $this->service->toggleActive($approver);

        $this->assertFalse($result->is_active);

        $result = $this->service->toggleActive($result);

        $this->assertTrue($result->is_active);
    }

    // ==========================================
    // getApproversByLevel Tests
    // ==========================================

    public function test_get_approvers_by_level_returns_correct_approvers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 2, 'is_active' => true]);

        $result = $this->service->getApproversByLevel(1);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->approval_level);
    }

    // ==========================================
    // isUserApprover Tests
    // ==========================================

    public function test_is_user_approver_returns_true_when_user_is_approver(): void
    {
        $user = User::factory()->create();
        GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->service->isUserApprover($user->id);

        $this->assertTrue($result);
    }

    public function test_is_user_approver_returns_false_when_user_is_not_approver(): void
    {
        $user = User::factory()->create();

        $result = $this->service->isUserApprover($user->id);

        $this->assertFalse($result);
    }

    // ==========================================
    // getDataForSettingsPage Tests
    // ==========================================

    public function test_get_data_for_settings_page_returns_required_data(): void
    {
        // Create eligible role and user
        $role = Role::firstOrCreate(
            ['name' => 'Admin Sarpras'],
            ['guard_name' => 'web', 'display_name' => 'Admin Sarpras']
        );
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->service->getDataForSettingsPage();

        $this->assertArrayHasKey('globalApprovers', $result);
        $this->assertArrayHasKey('availableUsers', $result);
        $this->assertArrayHasKey('availableLevels', $result);

        $this->assertCount(10, $result['availableLevels']);
    }

    public function test_get_data_for_settings_page_filters_eligible_users_by_role(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin Sarpras'],
            ['guard_name' => 'web', 'display_name' => 'Admin Sarpras']
        );
        $peminjamRole = Role::firstOrCreate(
            ['name' => 'Peminjam Mahasiswa'],
            ['guard_name' => 'web', 'display_name' => 'Peminjam Mahasiswa']
        );

        // Create users
        $adminUser = User::factory()->create(['status' => 'active', 'name' => 'Admin User']);
        $adminUser->assignRole($adminRole);

        $peminjamUser = User::factory()->create(['status' => 'active', 'name' => 'Peminjam User']);
        $peminjamUser->assignRole($peminjamRole);

        $result = $this->service->getDataForSettingsPage();

        // Only admin user should be in available users
        $availableUserIds = $result['availableUsers']->pluck('id')->toArray();
        $this->assertContains($adminUser->id, $availableUserIds);
        $this->assertNotContains($peminjamUser->id, $availableUserIds);
    }
}
