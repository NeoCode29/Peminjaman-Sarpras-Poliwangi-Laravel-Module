<?php

namespace App\Events;

use App\Models\Permission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Permission $permission, public array $changes)
    {
    }
}
