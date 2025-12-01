<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Modules\SaranaManagement\Entities\Sarana;

class SaranaAuditLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $action,
        public readonly Sarana $sarana,
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
