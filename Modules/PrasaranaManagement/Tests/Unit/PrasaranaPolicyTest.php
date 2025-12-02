<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Policies\PrasaranaPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PrasaranaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PrasaranaPolicy();

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

    public function test_user_with_manage_permission_can_crud_prasarana(): void
    {
        $user = $this->makeUserWith(['sarpras.manage']);
        $prasarana = Prasarana::factory()->make();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $prasarana));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $prasarana));
        $this->assertTrue($this->policy->delete($user, $prasarana));
    }

    public function test_user_without_manage_permission_cannot_access_prasarana(): void
    {
        $user = $this->makeUserWith();
        $prasarana = Prasarana::factory()->make();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $prasarana));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $prasarana));
        $this->assertFalse($this->policy->delete($user, $prasarana));
    }
}
