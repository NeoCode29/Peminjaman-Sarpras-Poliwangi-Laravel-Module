<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", now()->addDay(), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, ?string $type = null, ?string $group = 'general'): self
    {
        $type = $type ?? static::detectType($value);
        
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => static::prepareValue($value, $type),
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget('all_settings');

        return $setting;
    }

    /**
     * Get all settings grouped by group
     */
    public static function getAll(bool $grouped = false): array
    {
        return Cache::remember('all_settings', now()->addDay(), function () use ($grouped) {
            $settings = static::all();

            if ($grouped) {
                return $settings->groupBy('group')->map(function ($items) {
                    return $items->mapWithKeys(function ($item) {
                        return [$item->key => static::castValue($item->value, $item->type)];
                    });
                })->toArray();
            }

            return $settings->mapWithKeys(function ($item) {
                return [$item->key => static::castValue($item->value, $item->type)];
            })->toArray();
        });
    }

    /**
     * Get public settings (for frontend)
     */
    public static function getPublic(): array
    {
        return Cache::remember('public_settings', now()->addDay(), function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->key => static::castValue($item->value, $item->type)];
                })
                ->toArray();
        });
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue($value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => is_array($value) ? json_encode($value) : $value,
            default => (string) $value,
        };
    }

    /**
     * Detect value type
     */
    protected static function detectType($value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('all_settings');
        Cache::forget('public_settings');
        
        // Clear individual setting caches
        static::all()->each(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });
    }

    /**
     * Boot method
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}
