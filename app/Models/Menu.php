<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'label',
        'route',
        'url',
        'icon',
        'permission',
        'active_routes',
        'order',
        'is_active',
        'is_separator',
        'target',
    ];

    protected $casts = [
        'active_routes' => 'array',
        'is_active' => 'boolean',
        'is_separator' => 'boolean',
    ];

    /**
     * Relationship: Parent menu
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Relationship: Children menus
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    /**
     * Scope: Only active menus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only parent menus (no parent_id)
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Order by order column
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get sidebar menus with caching
     */
    public static function getSidebarMenus(?int $userId = null): array
    {
        $cacheKey = 'sidebar_menus' . ($userId ? "_user_{$userId}" : '');

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            return static::active()
                ->parents()
                ->ordered()
                ->with(['children' => function ($query) {
                    $query->active()->ordered();
                }])
                ->get()
                ->toArray();
        });
    }

    /**
     * Clear menu cache
     */
    public static function clearCache(?int $userId = null): void
    {
        $cacheKey = 'sidebar_menus' . ($userId ? "_user_{$userId}" : '');
        Cache::forget($cacheKey);
        
        // Clear for all users if no userId provided
        if (is_null($userId)) {
            Cache::flush();
        }
    }

    /**
     * Boot method to clear cache on model events
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
