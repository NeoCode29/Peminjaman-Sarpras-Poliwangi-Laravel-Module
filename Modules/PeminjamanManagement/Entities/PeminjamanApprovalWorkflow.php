<?php

namespace Modules\PeminjamanManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\PrasaranaManagement\Entities\Prasarana;

class PeminjamanApprovalWorkflow extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_approval_workflow';

    // Approval type constants
    public const TYPE_GLOBAL = 'global';
    public const TYPE_SARANA = 'sarana';
    public const TYPE_PRASARANA = 'prasarana';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_OVERRIDDEN = 'overridden';

    protected $fillable = [
        'peminjaman_id',
        'approver_id',
        'approval_type',
        'sarana_id',
        'prasarana_id',
        'approval_level',
        'status',
        'notes',
        'approved_at',
        'rejected_at',
        'overridden_by',
        'overridden_at',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'overridden_at' => 'datetime',
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
     * Get the approver user.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user who overrode.
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * Get the sarana (if approval type is sarana).
     */
    public function sarana(): BelongsTo
    {
        return $this->belongsTo(Sarana::class, 'sarana_id');
    }

    /**
     * Get the prasarana (if approval type is prasarana).
     */
    public function prasarana(): BelongsTo
    {
        return $this->belongsTo(Prasarana::class, 'prasarana_id');
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if workflow is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if workflow is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if workflow is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if workflow decision has been overridden.
     */
    public function isOverridden(): bool
    {
        return !is_null($this->overridden_at);
    }

    /**
     * Check if workflow is global approval.
     */
    public function isGlobal(): bool
    {
        return $this->approval_type === self::TYPE_GLOBAL;
    }

    /**
     * Check if workflow is specific sarana approval.
     */
    public function isSpecificSarana(): bool
    {
        return $this->approval_type === self::TYPE_SARANA;
    }

    /**
     * Check if workflow is specific prasarana approval.
     */
    public function isSpecificPrasarana(): bool
    {
        return $this->approval_type === self::TYPE_PRASARANA;
    }

    // ==================== ACCESSORS ====================

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_OVERRIDDEN => 'Di-override',
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
            self::STATUS_OVERRIDDEN => 'badge-info',
            default => 'badge-light',
        };
    }

    /**
     * Get approval type label.
     */
    public function getApprovalTypeLabelAttribute(): string
    {
        return match ($this->approval_type) {
            self::TYPE_GLOBAL => 'Approval Global',
            self::TYPE_SARANA => 'Approval Sarana',
            self::TYPE_PRASARANA => 'Approval Prasarana',
            default => ucfirst($this->approval_type),
        };
    }

    // ==================== ACTIONS ====================

    /**
     * Approve workflow.
     */
    public function approve(?string $notes = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->notes = $notes;
        $this->approved_at = now();
        $this->rejected_at = null;

        return $this->save();
    }

    /**
     * Reject workflow.
     */
    public function reject(?string $notes = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->notes = $notes;
        $this->rejected_at = now();
        $this->approved_at = null;

        return $this->save();
    }

    /**
     * Reset workflow to pending.
     */
    public function reset(): bool
    {
        $this->status = self::STATUS_PENDING;
        $this->notes = null;
        $this->approved_at = null;
        $this->rejected_at = null;

        return $this->save();
    }

    // ==================== SCOPES ====================

    /**
     * Scope for status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for approval type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('approval_type', $type);
    }

    /**
     * Scope for global approval.
     */
    public function scopeGlobal($query)
    {
        return $query->where('approval_type', self::TYPE_GLOBAL);
    }

    /**
     * Scope for specific sarana approval.
     */
    public function scopeSpecificSarana($query)
    {
        return $query->where('approval_type', self::TYPE_SARANA);
    }

    /**
     * Scope for specific prasarana approval.
     */
    public function scopeSpecificPrasarana($query)
    {
        return $query->where('approval_type', self::TYPE_PRASARANA);
    }

    /**
     * Scope for pending approval.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for approver.
     */
    public function scopeForApprover($query, int $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    /**
     * Scope for sarana.
     */
    public function scopeForSarana($query, int $saranaId)
    {
        return $query->where('sarana_id', $saranaId);
    }

    /**
     * Scope for prasarana.
     */
    public function scopeForPrasarana($query, int $prasaranaId)
    {
        return $query->where('prasarana_id', $prasaranaId);
    }

    /**
     * Scope for peminjaman.
     */
    public function scopeForPeminjaman($query, int $peminjamanId)
    {
        return $query->where('peminjaman_id', $peminjamanId);
    }

    // ==================== FACTORY ====================

    protected static function newFactory()
    {
        return \Modules\PeminjamanManagement\Database\factories\PeminjamanApprovalWorkflowFactory::new();
    }
}
