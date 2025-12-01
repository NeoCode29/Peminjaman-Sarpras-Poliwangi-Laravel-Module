<?php

namespace Modules\SaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Policies\KategoriSaranaPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KategoriSaranaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriSaranaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new KategoriSaranaPolicy();

        Permission::firstOrCreate([
            'name' => 'sarpras.manage',
            'guard_name' => 'web',
        ]);
    }

    protected function makeUserWithManagePermission(bool $hasPermission): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        if ($hasPermission) {
            $user->givePermissionTo('sarpras.manage');
        }

        return $user;
    }

    public function test_user_with_manage_permission_can_access_kategori_sarana(): void
    {
        $user = $this->makeUserWithManagePermission(true);
        $kategori = KategoriSarana::factory()->make();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $kategori));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $kategori));
        $this->assertTrue($this->policy->delete($user, $kategori));
    }

    public function test_user_without_manage_permission_cannot_access_kategori_sarana(): void
    {
        $user = $this->makeUserWithManagePermission(false);
        $kategori = KategoriSarana::factory()->make();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $kategori));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $kategori));
        $this->assertFalse($this->policy->delete($user, $kategori));
    }
}
