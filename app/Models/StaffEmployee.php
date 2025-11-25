<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'unit_id',
        'position_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function scopeByUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeByPosition($query, int $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nip', 'like', "%{$keyword}%")
            ->orWhereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%");
            });
    }
}
