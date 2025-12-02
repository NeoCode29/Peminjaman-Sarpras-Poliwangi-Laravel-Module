<?php

namespace Modules\SaranaManagement\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaranaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'sarpras.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sarpras.unit_manage', 'guard_name' => 'web']);
        // Permission untuk approver sarana, digunakan di policy & Blade
        Permission::firstOrCreate(['name' => 'sarpras.assign_specific_approver', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
        $this->adminUser->givePermissionTo(['sarpras.manage', 'sarpras.unit_manage']);

        $this->regularUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_view_sarana_index(): void
    {
        $kategori = KategoriSarana::factory()->create();
        Sarana::factory()->count(2)->create(['kategori_id' => $kategori->id]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('sarana.index'));

        $response->assertOk();
        $response->assertViewIs('saranamanagement::sarana.index');
        $response->assertViewHas(['saranas', 'kategoris', 'filters']);
    }

    /** @test */
    public function regular_user_cannot_view_sarana_index(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('sarana.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_store_new_sarana(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $payload = [
            'nama' => 'Laptop Uji',
            'kategori_id' => $kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'type' => 'pooled',
            'jumlah_total' => 5,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('sarana.store'), $payload);

        $response->assertRedirect(route('sarana.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('saranas', [
            'nama' => 'Laptop Uji',
            'kategori_id' => $kategori->id,
        ]);
    }

    /** @test */
    public function admin_can_view_sarana_detail_with_approvers(): void
    {
        $kategori = KategoriSarana::factory()->create();
        $sarana = Sarana::factory()->create(['kategori_id' => $kategori->id]);

        // Buat beberapa approver untuk memastikan paginator bekerja
        SaranaApprover::factory()->count(3)->create([
            'sarana_id' => $sarana->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('sarana.show', $sarana));

        $response->assertOk();
        $response->assertViewIs('saranamanagement::sarana.show');
        $response->assertViewHas(['sarana', 'availableApprovers', 'approvers']);
    }

    /** @test */
    public function admin_can_update_and_delete_sarana(): void
    {
        $kategori = KategoriSarana::factory()->create();
        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'nama' => 'Lama',
        ]);

        // Update
        $updatePayload = [
            'nama' => 'Baru',
            'kategori_id' => $kategori->id,
            'kondisi' => 'rusak_ringan',
            'status_ketersediaan' => 'dalam_perbaikan',
            'type' => 'pooled',
            'jumlah_total' => 3,
        ];

        $this->actingAs($this->adminUser)
            ->put(route('sarana.update', $sarana), $updatePayload)
            ->assertRedirect(route('sarana.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('saranas', [
            'id' => $sarana->id,
            'nama' => 'Baru',
        ]);

        // Delete
        $this->actingAs($this->adminUser)
            ->delete(route('sarana.destroy', $sarana))
            ->assertRedirect(route('sarana.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('saranas', ['id' => $sarana->id]);
    }
}
