<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_tokens';

    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_type',
        'scope',
        'expires_at',
    ];

    protected $casts = [
        'expires_in' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return now()->lessThan($this->expires_at);
    }
}
