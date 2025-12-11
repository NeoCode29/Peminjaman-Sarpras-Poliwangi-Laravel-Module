<?php

namespace Modules\PeminjamanManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Repositories\Interfaces\PeminjamanRepositoryInterface;
use Tests\TestCase;

class PeminjamanRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_count_active_for_user_returns_correct_number(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Peminjaman::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => Peminjaman::STATUS_PENDING,
        ]);

        Peminjaman::factory()->count(1)->create([
            'user_id' => $user->id,
            'status' => Peminjaman::STATUS_RETURNED,
        ]);

        /** @var PeminjamanRepositoryInterface $repo */
        $repo = $this->app->make(PeminjamanRepositoryInterface::class);

        $count = $repo->countActiveForUser($user->id);

        $this->assertEquals(2, $count);
    }
}
