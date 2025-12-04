<?php

namespace Modules\PeminjamanManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SaranaManagement\Entities\Sarana;

class PeminjamanItem extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_items';

    protected $fillable = [
        'peminjaman_id',
        'sarana_id',
        'qty_requested',
        'qty_approved',
        'notes',
    ];

    protected $casts = [
        'qty_requested' => 'integer',
        'qty_approved' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the peminjaman that owns this item.
     */
    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    /**
     * Get the sarana for this item.
     */
    public function sarana(): BelongsTo
    {
        return $this->belongsTo(Sarana::class);
    }

    /**
     * Get the assigned units for this item.
     */
    public function units(): HasMany
    {
        return $this->hasMany(PeminjamanItemUnit::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get effective approved quantity for reporting.
     */
    public function getApprovedQuantityAttribute(): int
    {
        $approved = $this->qty_approved;

        if (is_numeric($approved) && (int) $approved > 0) {
            return (int) $approved;
        }

        $requested = $this->qty_requested;

        if (is_numeric($requested) && (int) $requested > 0) {
            return (int) $requested;
        }

        if ($this->relationLoaded('units')) {
            return $this->units->count();
        }

        return $this->units()->count();
    }

    /**
     * Get the remaining quantity to approve.
     */
    public function getRemainingQtyAttribute(): int
    {
        return max(0, $this->qty_requested - ($this->qty_approved ?? 0));
    }

    /**
     * Get the approval percentage.
     */
    public function getApprovalPercentageAttribute(): float
    {
        if ($this->qty_requested == 0) {
            return 0;
        }

        return (($this->qty_approved ?? 0) / $this->qty_requested) * 100;
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if item is fully approved.
     */
    public function isFullyApproved(): bool
    {
        return ($this->qty_approved ?? 0) >= $this->qty_requested;
    }

    /**
     * Check if item is partially approved.
     */
    public function isPartiallyApproved(): bool
    {
        $approved = $this->qty_approved ?? 0;
        return $approved > 0 && $approved < $this->qty_requested;
    }

    /**
     * Check if item is not approved.
     */
    public function isNotApproved(): bool
    {
        return ($this->qty_approved ?? 0) == 0;
    }

    // ==================== SCOPES ====================

    /**
     * Scope for fully approved items.
     */
    public function scopeFullyApproved($query)
    {
        return $query->whereRaw('qty_approved >= qty_requested');
    }

    /**
     * Scope for partially approved items.
     */
    public function scopePartiallyApproved($query)
    {
        return $query->whereRaw('qty_approved > 0 AND qty_approved < qty_requested');
    }

    /**
     * Scope for not approved items.
     */
    public function scopeNotApproved($query)
    {
        return $query->where(function ($q) {
            $q->where('qty_approved', 0)
                ->orWhereNull('qty_approved');
        });
    }

    /**
     * Scope for peminjaman.
     */
    public function scopeForPeminjaman($query, int $peminjamanId)
    {
        return $query->where('peminjaman_id', $peminjamanId);
    }

    /**
     * Scope for sarana.
     */
    public function scopeForSarana($query, int $saranaId)
    {
        return $query->where('sarana_id', $saranaId);
    }

    // ==================== FACTORY ====================

    protected static function newFactory()
    {
        return \Modules\PeminjamanManagement\Database\factories\PeminjamanItemFactory::new();
    }
}
