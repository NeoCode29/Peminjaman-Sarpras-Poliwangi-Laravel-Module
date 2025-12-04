<?php

namespace Modules\PeminjamanManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SaranaManagement\Entities\SaranaUnit;

class PeminjamanItemUnit extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_item_units';

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'peminjaman_id',
        'peminjaman_item_id',
        'unit_id',
        'assigned_by',
        'assigned_at',
        'status',
        'released_by',
        'released_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the peminjaman item that owns this unit.
     */
    public function peminjamanItem(): BelongsTo
    {
        return $this->belongsTo(PeminjamanItem::class, 'peminjaman_item_id');
    }

    /**
     * Get the peminjaman directly.
     */
    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    /**
     * Get the sarana unit that was assigned.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(SaranaUnit::class, 'unit_id');
    }

    /**
     * Get the user who assigned this unit.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who released this unit.
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if unit is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if unit is released.
     */
    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active units.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for released units.
     */
    public function scopeReleased($query)
    {
        return $query->where('status', self::STATUS_RELEASED);
    }

    /**
     * Scope for peminjaman.
     */
    public function scopeForPeminjaman($query, int $peminjamanId)
    {
        return $query->where('peminjaman_id', $peminjamanId);
    }

    /**
     * Scope for peminjaman item.
     */
    public function scopeForPeminjamanItem($query, int $peminjamanItemId)
    {
        return $query->where('peminjaman_item_id', $peminjamanItemId);
    }

    /**
     * Scope for unit.
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }
}
