<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $permissionId,
        public string $permissionName,
        public ?int $deletedBy
    ) {
    }
}
