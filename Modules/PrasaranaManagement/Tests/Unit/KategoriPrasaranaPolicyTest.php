<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Policies\KategoriPrasaranaPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KategoriPrasaranaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriPrasaranaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new KategoriPrasaranaPolicy();

        Permission::firstOrCreate([
            'name' => 'sarpras.manage',
            'guard_name' => 'web',
        ]);
    }

    protected function makeUserWith(array $permissions = []): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_user_with_manage_permission_can_crud_kategori_prasarana(): void
    {
        $user = $this->makeUserWith(['sarpras.manage']);
        $kategori = KategoriPrasarana::factory()->make();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $kategori));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $kategori));
        $this->assertTrue($this->policy->delete($user, $kategori));
    }

    public function test_user_without_manage_permission_cannot_access_kategori_prasarana(): void
    {
        $user = $this->makeUserWith();
        $kategori = KategoriPrasarana::factory()->make();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $kategori));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $kategori));
        $this->assertFalse($this->policy->delete($user, $kategori));
    }
}
