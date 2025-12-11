<?php

namespace Modules\PeminjamanManagement\Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Policies\PeminjamanPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PeminjamanPolicyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'peminjaman.view',
            'peminjaman.validate_pickup',
            'peminjaman.validate_return',
        ] as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    protected function createUserWithRole(string $roleName): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_owner_can_view_own_peminjaman(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Peminjaman $peminjaman */
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new PeminjamanPolicy();

        $this->assertTrue($policy->view($user, $peminjaman));
    }

    public function test_admin_sarpras_can_validate_pickup_and_return(): void
    {
        $user = $this->createUserWithRole('Admin Sarpras');

        /** @var Peminjaman $peminjaman */
        $peminjaman = Peminjaman::factory()->create([
            'status' => Peminjaman::STATUS_APPROVED,
        ]);

        $policy = new PeminjamanPolicy();

        $this->assertTrue($policy->validatePickup($user, $peminjaman));

        $peminjaman->status = Peminjaman::STATUS_PICKED_UP;

        $this->assertTrue($policy->validateReturn($user, $peminjaman));
    }
}
