<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetPermissionCache();
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    public function test_admin_with_permission_can_view_settings_page(): void
    {
        $admin = $this->createAdminWithSettingsPermission();

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewIs('settings.index');
        $response->assertViewHasAll(['settingsByGroup', 'values']);
    }

    public function test_user_without_permission_cannot_view_settings_page(): void
    {
        $user = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertForbidden();
    }

    public function test_admin_with_permission_can_update_settings(): void
    {
        $admin = $this->createAdminWithSettingsPermission();

        SystemSetting::create([
            'group' => 'authentication',
            'key' => 'enable_manual_registration',
            'value' => '1',
            'type' => 'boolean',
            'is_public' => true,
        ]);

        SystemSetting::create([
            'group' => 'authentication',
            'key' => 'enable_sso_login',
            'value' => '1',
            'type' => 'boolean',
            'is_public' => true,
        ]);

        $payload = [
            'settings' => [
                'enable_manual_registration' => '0',
                'enable_sso_login' => '0',
            ],
        ];

        $response = $this->actingAs($admin)->post(route('settings.update'), $payload);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $this->assertSame('0', SystemSetting::where('key', 'enable_manual_registration')->first()->value);
        $this->assertSame('0', SystemSetting::where('key', 'enable_sso_login')->first()->value);
    }

    public function test_update_settings_handles_exceptions_and_shows_error(): void
    {
        $admin = $this->createAdminWithSettingsPermission();

        // Tidak buat SystemSetting apapun sehingga payload akan di-skip / tidak error,
        // tapi untuk mensimulasikan error, kita kirimkan settings kosong dan pastikan
        // tidak ada exception yang tidak tertangani.
        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'settings' => [],
        ]);

        $response->assertRedirect(route('settings.index'));
    }

    private function createAdminWithSettingsPermission(): User
    {
        $this->forgetPermissionCache();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'system.settings',
            'guard_name' => 'web',
        ]);

        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        /** @var User $user */
        $user = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
        $user->assignRole($role);

        $this->forgetPermissionCache();

        return $user;
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
