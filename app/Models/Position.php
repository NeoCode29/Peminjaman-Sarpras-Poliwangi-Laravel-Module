<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function staffEmployees(): HasMany
    {
        return $this->hasMany(StaffEmployee::class);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nama', 'like', "%{$keyword}%")
            ->orWhere('deskripsi', 'like', "%{$keyword}%");
    }
}
