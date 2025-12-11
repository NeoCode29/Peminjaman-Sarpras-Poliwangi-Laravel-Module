<?php

namespace Modules\PeminjamanManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuota extends Model
{
    use HasFactory;

    protected $table = 'user_quotas';

    protected $fillable = [
        'user_id',
        'active_borrowings',
        'max_borrowings',
    ];

    protected $casts = [
        'active_borrowings' => 'integer',
        'max_borrowings' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get remaining quota.
     */
    public function getRemainingQuotaAttribute(): int
    {
        return max(0, $this->max_borrowings - $this->active_borrowings);
    }

    /**
     * Check if user has quota available.
     */
    public function hasQuotaAvailable(): bool
    {
        return $this->active_borrowings < $this->max_borrowings;
    }

    /**
     * Check if quota is exhausted.
     */
    public function isQuotaExhausted(): bool
    {
        return $this->active_borrowings >= $this->max_borrowings;
    }

    // ==================== ACTIONS ====================

    /**
     * Increment active borrowings.
     */
    public function increment(): bool
    {
        $this->active_borrowings++;
        return $this->save();
    }

    /**
     * Decrement active borrowings.
     */
    public function decrement(): bool
    {
        if ($this->active_borrowings > 0) {
            $this->active_borrowings--;
        }
        return $this->save();
    }

    /**
     * Reset active borrowings to 0.
     */
    public function reset(): bool
    {
        $this->active_borrowings = 0;
        return $this->save();
    }

    // ==================== SCOPES ====================

    /**
     * Scope for user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for users with available quota.
     */
    public function scopeWithAvailableQuota($query)
    {
        return $query->whereRaw('active_borrowings < max_borrowings');
    }

    /**
     * Scope for users with exhausted quota.
     */
    public function scopeWithExhaustedQuota($query)
    {
        return $query->whereRaw('active_borrowings >= max_borrowings');
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get or create quota for user.
     */
    public static function getOrCreateForUser(int $userId, int $maxBorrowings = 3): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'active_borrowings' => 0,
                'max_borrowings' => $maxBorrowings,
            ]
        );
    }
}
