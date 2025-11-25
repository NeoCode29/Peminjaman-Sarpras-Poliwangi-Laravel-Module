<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, config('permission.table_names.model_has_roles'), 'role_id', 'model_id')
            ->withPivot('model_type')
            ->wherePivot('model_type', User::class);
    }

    public function getDisplayNameAttribute(?string $value): string
    {
        return $value ?: ucfirst(str_replace('_', ' ', $this->name));
    }
}
