<?php

namespace App\Events;

use App\Models\Role;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Role $role, public array $changes)
    {
    }
}
