<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Authentication Settings
            [
                'key' => 'enable_manual_registration',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'authentication',
                'description' => 'Aktifkan registrasi manual (tanpa SSO)',
                'is_public' => true,
            ],
            [
                'key' => 'enable_sso_login',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'authentication',
                'description' => 'Aktifkan login dengan SSO',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✅ System settings seeded successfully!');
    }
}
