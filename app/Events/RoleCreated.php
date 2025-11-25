<?php

namespace App\Events;

use App\Models\Role;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Role $role)
    {
    }
}
