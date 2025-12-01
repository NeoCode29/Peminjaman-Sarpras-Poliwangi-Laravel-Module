<?php

namespace Modules\SaranaManagement\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;

class SaranaFactory extends Factory
{
    protected $model = Sarana::class;

    public function definition(): array
    {
        return [
            'kode_sarana' => 'SRN-' . str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'nama' => $this->faker->words(3, true),
            'kategori_id' => KategoriSarana::factory(),
            'merk' => $this->faker->optional()->company(),
            'spesifikasi' => $this->faker->optional()->sentence(),
            'kondisi' => $this->faker->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'dalam_perbaikan']),
            'status_ketersediaan' => $this->faker->randomElement(['tersedia', 'dipinjam', 'dalam_perbaikan', 'tidak_tersedia']),
            'type' => $this->faker->randomElement(['pooled', 'serialized']),
            'jumlah_total' => $this->faker->numberBetween(1, 20),
            'jumlah_tersedia' => $this->faker->numberBetween(0, 20),
            'jumlah_rusak' => 0,
            'jumlah_maintenance' => 0,
            'jumlah_hilang' => 0,
            'tahun_perolehan' => $this->faker->numberBetween(2015, (int) date('Y')),
            'nilai_perolehan' => $this->faker->numberBetween(1_000_000, 50_000_000),
            'lokasi_penyimpanan' => $this->faker->optional()->sentence(3),
            'foto' => null,
            'keterangan' => $this->faker->optional()->sentence(),
        ];
    }
}
