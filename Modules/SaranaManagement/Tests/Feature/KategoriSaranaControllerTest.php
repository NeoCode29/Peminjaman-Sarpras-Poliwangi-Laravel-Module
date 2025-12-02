<?php

namespace Modules\SaranaManagement\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Services\KategoriSaranaService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KategoriSaranaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'sarpras.manage', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
        $this->adminUser->givePermissionTo('sarpras.manage');

        $this->regularUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_view_kategori_index(): void
    {
        KategoriSarana::factory()->count(2)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('kategori-sarana.index'));

        $response->assertOk();
        $response->assertViewIs('saranamanagement::kategori.index');
        $response->assertViewHas(['kategoris', 'filters']);
    }

    /** @test */
    public function regular_user_cannot_view_kategori_index(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('kategori-sarana.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_store_and_update_kategori_sarana(): void
    {
        // Store
        $payload = [
            'nama' => 'Elektronik',
            'deskripsi' => 'Peralatan elektronik',
        ];

        $this->actingAs($this->adminUser)
            ->post(route('kategori-sarana.store'), $payload)
            ->assertRedirect(route('kategori-sarana.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategori_saranas', [
            'nama' => 'Elektronik',
        ]);

        $kategori = KategoriSarana::first();

        // Update
        $updatePayload = [
            'nama' => 'Elektronik Kantor',
            'deskripsi' => 'Peralatan kantor',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('kategori-sarana.update', $kategori), $updatePayload)
            ->assertRedirect(route('kategori-sarana.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategori_saranas', [
            'id' => $kategori->id,
            'nama' => 'Elektronik Kantor',
        ]);
    }

    /** @test */
    public function admin_can_delete_kategori_without_sarana_but_not_with_sarana(): void
    {
        $kategoriTanpaSarana = KategoriSarana::factory()->create();

        $this->actingAs($this->adminUser)
            ->delete(route('kategori-sarana.destroy', $kategoriTanpaSarana))
            ->assertRedirect(route('kategori-sarana.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kategori_saranas', ['id' => $kategoriTanpaSarana->id]);

        // Kategori dengan sarana
        $kategoriDenganSarana = KategoriSarana::factory()->create();
        Sarana::factory()->create(['kategori_id' => $kategoriDenganSarana->id]);

        $this->actingAs($this->adminUser)
            ->delete(route('kategori-sarana.destroy', $kategoriDenganSarana))
            ->assertRedirect(route('kategori-sarana.show', $kategoriDenganSarana))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('kategori_saranas', ['id' => $kategoriDenganSarana->id]);
    }
}
