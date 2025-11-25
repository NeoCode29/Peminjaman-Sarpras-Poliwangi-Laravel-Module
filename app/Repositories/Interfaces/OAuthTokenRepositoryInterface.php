<?php

namespace App\Repositories\Interfaces;

use App\Models\OAuthToken;

interface OAuthTokenRepositoryInterface
{
    public function findByUserId(int $userId): ?OAuthToken;

    public function create(array $attributes): OAuthToken;

    public function deleteByUserId(int $userId): void;
}
