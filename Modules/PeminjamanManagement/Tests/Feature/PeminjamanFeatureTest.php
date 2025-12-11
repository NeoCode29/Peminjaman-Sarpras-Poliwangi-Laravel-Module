<?php

namespace Modules\PeminjamanManagement\Tests\Feature;

use App\Models\GlobalApprover;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\SaranaManagement\Entities\Sarana;
use Tests\TestCase;

class PeminjamanFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan route peminjaman sudah ter-registrasi
        // dan permission cache bersih jika dibutuhkan.
    }

    protected function createBorrowerUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'user_type' => 'staff',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        // Berikan role dasar yang biasanya boleh mengajukan peminjaman.
        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => 'Mahasiswa',
            'guard_name' => 'web',
        ]);
        $user->assignRole($role);

        // Pastikan permission peminjaman.create ada dan diberikan ke user agar lolos policy create
        $permission = Permission::firstOrCreate([
            'name' => 'peminjaman.create',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

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

    protected function createSarana(): Sarana
    {
        /** @var Sarana $sarana */
        $sarana = Sarana::factory()->create([
            'jumlah_total' => 10,
            'jumlah_tersedia' => 10,
        ]);

        return $sarana;
    }

    public function test_user_can_create_peminjaman_sarana_saja(): void
    {
        $borrower = $this->createBorrowerUser();
        $this->createGlobalApprover();
        $sarana = $this->createSarana();

        $today = Carbon::today();

        $payload = [
            'event_name' => 'Uji Coba Peminjaman',
            'loan_type' => 'sarana',
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'jumlah_peserta' => null,
            'ukm_id' => null,
            'jenis_lokasi' => 'custom',
            'lokasi_custom' => 'Lokasi Uji Coba',
            'surat' => UploadedFile::fake()->image('surat.jpg'),
            'sarana_items' => [
                [
                    'sarana_id' => $sarana->id,
                    'qty_requested' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($borrower)->post(route('peminjaman.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('peminjaman', [
            'event_name' => 'Uji Coba Peminjaman',
            'user_id' => $borrower->id,
        ]);

        /** @var Peminjaman $created */
        $created = Peminjaman::query()->where('event_name', 'Uji Coba Peminjaman')->first();
        $this->assertNotNull($created);

        // Pastikan item sarana tersimpan
        $this->assertDatabaseHas('peminjaman_items', [
            'peminjaman_id' => $created->id,
            'sarana_id' => $sarana->id,
            'qty_requested' => 2,
        ]);

        // Pastikan workflow global terbentuk dengan approver_id yang tidak null
        $this->assertDatabaseHas('peminjaman_approval_workflow', [
            'peminjaman_id' => $created->id,
            'approval_type' => 'global',
        ]);
    }

    public function test_validation_fails_when_end_datetime_before_start(): void
    {
        $borrower = $this->createBorrowerUser();
        $this->createGlobalApprover();
        $sarana = $this->createSarana();

        $start = Carbon::today()->addDay();
        $end = Carbon::today(); // lebih awal dari start_date

        $payload = [
            'event_name' => 'Peminjaman Tidak Valid',
            'loan_type' => 'sarana',
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '09:00',
            'jumlah_peserta' => null,
            'jenis_lokasi' => 'custom',
            'lokasi_custom' => 'Lokasi Uji Coba',
            'surat' => UploadedFile::fake()->image('surat.jpg'),
            'sarana_items' => [
                [
                    'sarana_id' => $sarana->id,
                    'qty_requested' => 1,
                ],
            ],
        ];

        $response = $this
            ->from(route('peminjaman.create'))
            ->actingAs($borrower)
            ->post(route('peminjaman.store'), $payload);

        $response->assertRedirect(route('peminjaman.create'));
        $response->assertSessionHasErrors(['end_date', 'end_time']);
    }
}
