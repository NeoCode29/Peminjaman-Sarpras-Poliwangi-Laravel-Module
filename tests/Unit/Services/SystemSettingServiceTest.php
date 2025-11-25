<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
    use DatabaseMigrations;

    private SystemSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(SystemSettingService::class);
    }

    /** @test */
    public function it_returns_settings_grouped_for_page(): void
    {
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

        $result = $this->service->getSettingsForPage();

        $this->assertArrayHasKey('settingsByGroup', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertTrue($result['settingsByGroup']->has('authentication'));
    }

    /** @test */
    public function it_updates_multiple_settings_and_ignores_unknown_keys(): void
    {
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

        $this->service->updateSettings([
            'enable_manual_registration' => '0',
            'enable_sso_login' => '0',
            'unknown_key' => 'ignored',
        ]);

        $this->assertSame('0', SystemSetting::where('key', 'enable_manual_registration')->first()->value);
        $this->assertSame('0', SystemSetting::where('key', 'enable_sso_login')->first()->value);
    }
}
