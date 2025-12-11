<?php

namespace Modules\PeminjamanManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\PeminjamanManagement\Entities\Peminjaman;

class PeminjamanStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Peminjaman $peminjaman,
        public ?string $oldStatus,
        public string $newStatus,
    ) {
    }
}
