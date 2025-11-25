<?php

namespace App\Repositories;

use App\Models\OAuthToken;
use App\Repositories\Interfaces\OAuthTokenRepositoryInterface;

class OAuthTokenRepository implements OAuthTokenRepositoryInterface
{
    public function findByUserId(int $userId): ?OAuthToken
    {
        return OAuthToken::where('user_id', $userId)->first();
    }

    public function create(array $attributes): OAuthToken
    {
        return OAuthToken::create($attributes);
    }

    public function deleteByUserId(int $userId): void
    {
        OAuthToken::where('user_id', $userId)->delete();
    }
}
