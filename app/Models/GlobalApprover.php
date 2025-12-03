<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalApprover extends Model
{
    use HasFactory;

    protected $table = 'global_approvers';

    protected $fillable = [
        'user_id',
        'approval_level',
        'is_active',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Relasi ke user yang menjadi approver
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope untuk approver aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk approver tidak aktif
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope untuk level tertentu
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('approval_level', $level);
    }

    /**
     * Scope untuk level primary (1)
     */
    public function scopePrimary($query)
    {
        return $query->where('approval_level', 1);
    }

    /**
     * Scope untuk level secondary (2)
     */
    public function scopeSecondary($query)
    {
        return $query->where('approval_level', 2);
    }

    /**
     * Scope untuk level tertiary (3)
     */
    public function scopeTertiary($query)
    {
        return $query->where('approval_level', 3);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get level label
     */
    public function getLevelLabelAttribute(): string
    {
        return match ($this->approval_level) {
            1 => 'Primary',
            2 => 'Secondary',
            3 => 'Tertiary',
            default => "Level {$this->approval_level}",
        };
    }

    /**
     * Get level badge class
     */
    public function getLevelBadgeClassAttribute(): string
    {
        return match ($this->approval_level) {
            1 => 'badge-primary',
            2 => 'badge-secondary',
            3 => 'badge-info',
            default => 'badge-light',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Tidak Aktif';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active ? 'badge-success' : 'badge-danger';
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Check if this approver can override another approver
     */
    public function canOverride(GlobalApprover $otherApprover): bool
    {
        // Lower level number = higher authority
        return $this->approval_level < $otherApprover->approval_level;
    }

    /**
     * Get all approvers with higher level (lower authority)
     */
    public function getLowerAuthorityApprovers()
    {
        return static::where('approval_level', '>', $this->approval_level)
            ->active()
            ->get();
    }

    /**
     * Get all approvers with lower level (higher authority)
     */
    public function getHigherAuthorityApprovers()
    {
        return static::where('approval_level', '<', $this->approval_level)
            ->active()
            ->get();
    }

    /**
     * Check if user is already a global approver
     */
    public static function isUserApprover(int $userId): bool
    {
        return static::where('user_id', $userId)->exists();
    }

    /**
     * Check if user is an active global approver
     */
    public static function isUserActiveApprover(int $userId): bool
    {
        return static::where('user_id', $userId)->active()->exists();
    }
}
