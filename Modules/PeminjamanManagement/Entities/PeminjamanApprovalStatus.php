<?php

namespace Modules\PeminjamanManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanApprovalStatus extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_approval_status';

    // Overall status constants
    public const OVERALL_PENDING = 'pending';
    public const OVERALL_APPROVED = 'approved';
    public const OVERALL_PARTIALLY_APPROVED = 'partially_approved';
    public const OVERALL_REJECTED = 'rejected';

    // Global status constants
    public const GLOBAL_PENDING = 'pending';
    public const GLOBAL_APPROVED = 'approved';
    public const GLOBAL_REJECTED = 'rejected';

    protected $fillable = [
        'peminjaman_id',
        'overall_status',
        'global_approval_status',
        'global_approved_by',
        'global_approved_at',
        'global_rejected_by',
        'global_rejected_at',
        'global_rejection_reason',
        'specific_approval_summary',
    ];

    protected $casts = [
        'global_approved_at' => 'datetime',
        'global_rejected_at' => 'datetime',
        'specific_approval_summary' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the peminjaman.
     */
    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    /**
     * Get the user who approved global.
     */
    public function globalApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'global_approved_by');
    }

    /**
     * Get the user who rejected global.
     */
    public function globalRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'global_rejected_by');
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if overall status is pending.
     */
    public function isPending(): bool
    {
        return $this->overall_status === self::OVERALL_PENDING;
    }

    /**
     * Check if overall status is partially approved.
     */
    public function isPartiallyApproved(): bool
    {
        return $this->overall_status === self::OVERALL_PARTIALLY_APPROVED;
    }

    /**
     * Check if overall status is approved.
     */
    public function isApproved(): bool
    {
        return $this->overall_status === self::OVERALL_APPROVED;
    }

    /**
     * Check if overall status is rejected.
     */
    public function isRejected(): bool
    {
        return $this->overall_status === self::OVERALL_REJECTED;
    }

    /**
     * Check if global status is pending.
     */
    public function isGlobalPending(): bool
    {
        return $this->global_approval_status === self::GLOBAL_PENDING;
    }

    /**
     * Check if global status is approved.
     */
    public function isGlobalApproved(): bool
    {
        return $this->global_approval_status === self::GLOBAL_APPROVED;
    }

    /**
     * Check if global status is rejected.
     */
    public function isGlobalRejected(): bool
    {
        return $this->global_approval_status === self::GLOBAL_REJECTED;
    }

    // ==================== ACCESSORS ====================

    /**
     * Get overall status label.
     */
    public function getOverallStatusLabelAttribute(): string
    {
        return match ($this->overall_status) {
            self::OVERALL_PENDING => 'Menunggu',
            self::OVERALL_PARTIALLY_APPROVED => 'Disetujui Sebagian',
            self::OVERALL_APPROVED => 'Disetujui',
            self::OVERALL_REJECTED => 'Ditolak',
            default => ucfirst($this->overall_status),
        };
    }

    /**
     * Get overall status badge class.
     */
    public function getOverallStatusBadgeClassAttribute(): string
    {
        return match ($this->overall_status) {
            self::OVERALL_PENDING => 'badge-warning',
            self::OVERALL_PARTIALLY_APPROVED => 'badge-info',
            self::OVERALL_APPROVED => 'badge-success',
            self::OVERALL_REJECTED => 'badge-danger',
            default => 'badge-light',
        };
    }

    /**
     * Get global status label.
     */
    public function getGlobalStatusLabelAttribute(): string
    {
        return match ($this->global_approval_status) {
            self::GLOBAL_PENDING => 'Menunggu',
            self::GLOBAL_APPROVED => 'Disetujui',
            self::GLOBAL_REJECTED => 'Ditolak',
            default => ucfirst($this->global_approval_status),
        };
    }

    /**
     * Get global status badge class.
     */
    public function getGlobalStatusBadgeClassAttribute(): string
    {
        return match ($this->global_approval_status) {
            self::GLOBAL_PENDING => 'badge-warning',
            self::GLOBAL_APPROVED => 'badge-success',
            self::GLOBAL_REJECTED => 'badge-danger',
            default => 'badge-light',
        };
    }

    // ==================== ACTIONS ====================

    /**
     * Set global approval.
     */
    public function setGlobalApproval(int $userId, ?string $reason = null): bool
    {
        $this->global_approval_status = self::GLOBAL_APPROVED;
        $this->global_approved_by = $userId;
        $this->global_approved_at = now();
        $this->global_rejected_by = null;
        $this->global_rejected_at = null;
        $this->global_rejection_reason = null;

        return $this->save();
    }

    /**
     * Set global rejection.
     */
    public function setGlobalRejection(int $userId, ?string $reason = null): bool
    {
        $this->global_approval_status = self::GLOBAL_REJECTED;
        $this->global_rejected_by = $userId;
        $this->global_rejected_at = now();
        $this->global_rejection_reason = $reason;
        $this->global_approved_by = null;
        $this->global_approved_at = null;

        return $this->save();
    }

    /**
     * Update overall status based on workflow status.
     */
    public function updateOverallStatus(): bool
    {
        $peminjamanId = $this->peminjaman_id;

        // Get all workflow for this peminjaman
        $workflows = PeminjamanApprovalWorkflow::where('peminjaman_id', $peminjamanId)->get();

        if ($workflows->isEmpty()) {
            $this->overall_status = self::OVERALL_PENDING;
            return $this->save();
        }

        // Check global approval status
        $globalWorkflows = $workflows->where('approval_type', 'global');
        if ($globalWorkflows->isNotEmpty()) {
            $globalRejected = $globalWorkflows->where('status', 'rejected')->isNotEmpty();
            $globalApproved = $globalWorkflows->where('status', 'approved')->isNotEmpty();

            if ($globalRejected) {
                $this->global_approval_status = self::GLOBAL_REJECTED;
                $this->overall_status = self::OVERALL_REJECTED;
                return $this->save();
            } elseif ($globalApproved) {
                $this->global_approval_status = self::GLOBAL_APPROVED;
            } else {
                $this->global_approval_status = self::GLOBAL_PENDING;
            }
        }

        // Check specific approvals
        $specificWorkflows = $workflows->whereIn('approval_type', ['sarana', 'prasarana']);

        if ($specificWorkflows->isEmpty()) {
            // No specific approvals needed, use global status
            $this->overall_status = $this->global_approval_status === self::GLOBAL_APPROVED
                ? self::OVERALL_APPROVED
                : self::OVERALL_PENDING;
            return $this->save();
        }

        $approvedCount = $specificWorkflows->where('status', 'approved')->count();
        $rejectedCount = $specificWorkflows->where('status', 'rejected')->count();
        $totalCount = $specificWorkflows->count();

        if ($rejectedCount > 0 && $approvedCount > 0) {
            $this->overall_status = self::OVERALL_PARTIALLY_APPROVED;
        } elseif ($approvedCount === $totalCount) {
            $this->overall_status = self::OVERALL_APPROVED;
        } elseif ($rejectedCount === $totalCount) {
            $this->overall_status = self::OVERALL_REJECTED;
        } else {
            $this->overall_status = self::OVERALL_PENDING;
        }

        // Global must be approved for overall to be approved
        if ($globalWorkflows->isNotEmpty() && $this->global_approval_status !== self::GLOBAL_APPROVED && $this->overall_status === self::OVERALL_APPROVED) {
            $this->overall_status = self::OVERALL_PENDING;
        }

        return $this->save();
    }

    // ==================== SCOPES ====================

    /**
     * Scope for overall status.
     */
    public function scopeByOverallStatus($query, string $status)
    {
        return $query->where('overall_status', $status);
    }

    /**
     * Scope for global status.
     */
    public function scopeByGlobalStatus($query, string $status)
    {
        return $query->where('global_approval_status', $status);
    }

    /**
     * Scope for pending.
     */
    public function scopePending($query)
    {
        return $query->where('overall_status', self::OVERALL_PENDING);
    }

    /**
     * Scope for partially approved.
     */
    public function scopePartiallyApproved($query)
    {
        return $query->where('overall_status', self::OVERALL_PARTIALLY_APPROVED);
    }

    /**
     * Scope for approved.
     */
    public function scopeApproved($query)
    {
        return $query->where('overall_status', self::OVERALL_APPROVED);
    }

    /**
     * Scope for rejected.
     */
    public function scopeRejected($query)
    {
        return $query->where('overall_status', self::OVERALL_REJECTED);
    }
}
