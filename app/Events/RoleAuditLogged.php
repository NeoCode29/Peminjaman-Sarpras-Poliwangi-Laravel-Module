<?php

namespace App\Events;

use App\Models\Role;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class RoleAuditLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $action,
        public readonly Role $role,
        public readonly array $attributes,
        public readonly array $original,
        public readonly ?int $performedBy,
        public readonly ?string $performedByType,
        public readonly ?string $context = null,
        public readonly array $metadata = []
    ) {
    }

    public function changes(): array
    {
        return Arr::except(array_diff_assoc($this->attributes, $this->original), ['updated_at']);
    }
}
