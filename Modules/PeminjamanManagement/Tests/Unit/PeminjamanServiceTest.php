<?php

namespace Modules\PeminjamanManagement\Tests\Unit;

use App\Models\GlobalApprover;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalWorkflow;
use Modules\PeminjamanManagement\Services\PeminjamanService;
use Modules\SaranaManagement\Entities\Sarana;
use Tests\TestCase;

class PeminjamanServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function createBorrower(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        return $user;
    }

    protected function createGlobalApprover(): User
    {
        /** @var User $approver */
        $approver = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        GlobalApprover::create([
            'user_id' => $approver->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        return $approver;
    }

    protected function createPooledSarana(int $jumlahTersedia = 10): Sarana
    {
        /** @var Sarana $sarana */
        $sarana = Sarana::factory()->create([
            'type' => 'pooled',
            'jumlah_total' => $jumlahTersedia,
            'jumlah_tersedia' => $jumlahTersedia,
        ]);

        return $sarana;
    }

    public function test_create_peminjaman_creates_items_and_approval_workflows(): void
    {
        $borrower = $this->createBorrower();
        $this->createGlobalApprover();
        $sarana = $this->createPooledSarana();

        /** @var PeminjamanService $service */
        $service = $this->app->make(PeminjamanService::class);

        $data = [
            'user_id' => $borrower->id,
            'event_name' => 'Unit Test Peminjaman',
            'loan_type' => 'sarana',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'jenis_lokasi' => 'custom',
            'lokasi_custom' => 'Ruang Unit Test',
        ];

        $saranaItems = [
            [
                'sarana_id' => $sarana->id,
                'qty_requested' => 3,
            ],
        ];

        /** @var Peminjaman $created */
        $created = $service->createPeminjaman($data, $saranaItems);

        $this->assertInstanceOf(Peminjaman::class, $created);
        $this->assertEquals('Unit Test Peminjaman', $created->event_name);
        $this->assertEquals($borrower->id, $created->user_id);

        $this->assertDatabaseHas('peminjaman_items', [
            'peminjaman_id' => $created->id,
            'sarana_id' => $sarana->id,
            'qty_requested' => 3,
        ]);

        $this->assertDatabaseHas('peminjaman_approval_status', [
            'peminjaman_id' => $created->id,
        ]);

        $this->assertDatabaseHas('peminjaman_approval_workflow', [
            'peminjaman_id' => $created->id,
            'approval_type' => PeminjamanApprovalWorkflow::TYPE_GLOBAL,
        ]);
    }
}
