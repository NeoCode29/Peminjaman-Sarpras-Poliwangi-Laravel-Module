<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class SystemSettingService
{
    /**
     * Get data needed for the settings page.
     */
    public function getSettingsForPage(): array
    {
        $settingsByGroup = SystemSetting::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        $values = SystemSetting::getAll(grouped: false);

        return [
            'settingsByGroup' => $settingsByGroup,
            'values' => $values,
        ];
    }

    /**
     * Update multiple system settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            try {
                SystemSetting::set($key, $value, $setting->type, $setting->group);
            } catch (\Throwable $e) {
                Log::error('Failed to update system setting', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
