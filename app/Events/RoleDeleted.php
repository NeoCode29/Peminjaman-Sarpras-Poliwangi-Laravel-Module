<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $roleId,
        public string $roleName,
        public ?int $deletedBy
    ) {
    }
}
