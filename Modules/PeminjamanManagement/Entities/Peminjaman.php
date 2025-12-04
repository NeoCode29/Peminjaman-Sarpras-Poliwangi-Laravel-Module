<?php

namespace Modules\PeminjamanManagement\Entities;

use App\Models\User;
use App\Models\Ukm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\PrasaranaManagement\Entities\Prasarana;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'prasarana_id',
        'lokasi_custom',
        'jumlah_peserta',
        'ukm_id',
        'event_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'konflik',
        'surat_path',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'pickup_validated_by',
        'pickup_validated_at',
        'return_validated_by',
        'return_validated_at',
        'cancelled_by',
        'cancelled_reason',
        'cancelled_at',
        'foto_pickup_path',
        'foto_return_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
        'pickup_validated_at' => 'datetime',
        'return_validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'jumlah_peserta' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who made the peminjaman.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prasarana for this peminjaman.
     */
    public function prasarana(): BelongsTo
    {
        return $this->belongsTo(Prasarana::class);
    }

    /**
     * Get the UKM for this peminjaman (if mahasiswa).
     */
    public function ukm(): BelongsTo
    {
        return $this->belongsTo(Ukm::class, 'ukm_id');
    }

    /**
     * Get the user who approved this peminjaman.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who validated pickup.
     */
    public function pickupValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pickup_validated_by');
    }

    /**
     * Get the user who validated return.
     */
    public function returnValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_validated_by');
    }

    /**
     * Get the user who cancelled this peminjaman.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the peminjaman items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanItem::class);
    }

    /**
     * Get the assigned units through items.
     */
    public function itemUnits()
    {
        return $this->hasManyThrough(PeminjamanItemUnit::class, PeminjamanItem::class);
    }

    /**
     * Get the approval workflow.
     */
    public function approvalWorkflow(): HasMany
    {
        return $this->hasMany(PeminjamanApprovalWorkflow::class);
    }

    /**
     * Get the approval status.
     */
    public function approvalStatus(): HasOne
    {
        return $this->hasOne(PeminjamanApprovalStatus::class);
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if peminjaman is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if peminjaman is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if peminjaman is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if peminjaman is picked up.
     */
    public function isPickedUp(): bool
    {
        return $this->status === self::STATUS_PICKED_UP;
    }

    /**
     * Check if peminjaman is returned.
     */
    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    /**
     * Check if peminjaman is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if peminjaman is in active status (not finished).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_PICKED_UP,
        ]);
    }

    // ==================== ACCESSORS ====================

    /**
     * Determine if any approval workflow step has been overridden.
     */
    public function getHasOverrideAttribute(): bool
    {
        if ($this->relationLoaded('approvalWorkflow')) {
            return $this->approvalWorkflow->contains(fn ($workflow) => $workflow->isOverridden());
        }

        return $this->approvalWorkflow()->whereNotNull('overridden_at')->exists();
    }

    /**
     * Determine if peminjaman is currently marked as conflict group.
     */
    public function getIsKonflikAttribute(): bool
    {
        return !empty($this->konflik);
    }

    /**
     * Get the duration in days.
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get the duration in hours.
     */
    public function getDurationInHours(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = $this->start_date->copy()->setTimeFromTimeString($this->start_time);
        $end = $this->end_date->copy()->setTimeFromTimeString($this->end_time);

        return $start->diffInHours($end);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_PICKED_UP => 'Sedang Dipinjam',
            self::STATUS_RETURNED => 'Dikembalikan',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_PICKED_UP => 'badge-info',
            self::STATUS_RETURNED => 'badge-primary',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-light',
        };
    }

    /**
     * Display status badge accessor.
     */
    public function getDisplayStatusBadgeAttribute(): array
    {
        $status = $this->status ?? self::STATUS_PENDING;
        $label = $this->status_label;
        $class = 'status-' . $status;

        $approvalStatus = $this->relationLoaded('approvalStatus')
            ? $this->getRelation('approvalStatus')
            : $this->approvalStatus()->first();

        $globalStatus = optional($approvalStatus)->global_approval_status;
        $overallStatus = optional($approvalStatus)->overall_status;

        if ($globalStatus === 'approved' && $overallStatus === 'pending') {
            return [
                'label' => 'Disetujui Global',
                'class' => 'status-approved',
            ];
        }

        if ($globalStatus === 'rejected' && $overallStatus === 'pending') {
            return [
                'label' => 'Ditolak Global',
                'class' => 'status-rejected',
            ];
        }

        return [
            'label' => $label,
            'class' => $class,
        ];
    }

    /**
     * Get surat URL.
     */
    public function getSuratUrlAttribute(): ?string
    {
        if (!$this->surat_path) {
            return null;
        }

        return asset('storage/' . $this->surat_path);
    }

    /**
     * Get foto pickup URL.
     */
    public function getFotoPickupUrlAttribute(): ?string
    {
        if (!$this->foto_pickup_path) {
            return null;
        }

        return asset('storage/' . $this->foto_pickup_path);
    }

    /**
     * Get foto return URL.
     */
    public function getFotoReturnUrlAttribute(): ?string
    {
        if (!$this->foto_return_path) {
            return null;
        }

        return asset('storage/' . $this->foto_return_path);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for active peminjaman (not cancelled or returned).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_PICKED_UP,
        ]);
    }

    /**
     * Scope for pending approval.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved peminjaman.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected peminjaman.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for picked up peminjaman.
     */
    public function scopePickedUp($query)
    {
        return $query->where('status', self::STATUS_PICKED_UP);
    }

    /**
     * Scope for returned peminjaman.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    /**
     * Scope for cancelled peminjaman.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for user's peminjaman.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Scope for search.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('event_name', 'like', "%{$search}%")
                ->orWhere('lokasi_custom', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('prasarana', function ($prasaranaQuery) use ($search) {
                    $prasaranaQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Scope for konflik group.
     */
    public function scopeInKonflikGroup($query, string $konflikCode)
    {
        return $query->where('konflik', $konflikCode);
    }

    // ==================== FACTORY ====================

    protected static function newFactory()
    {
        return \Modules\PeminjamanManagement\Database\factories\PeminjamanFactory::new();
    }
}
