<?php

namespace Database\Factories\Modules\PrasaranaManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Entities\Prasarana;

class PrasaranaFactory extends Factory
{
    protected $model = Prasarana::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(2),
            'kategori_id' => KategoriPrasarana::factory(),
            'description' => $this->faker->optional()->paragraph(1),
            'lokasi' => $this->faker->optional()->streetAddress(),
            'kapasitas' => $this->faker->optional()->numberBetween(10, 300),
            'status' => $this->faker->randomElement(['tersedia', 'rusak', 'maintenance']),
            'created_by' => User::factory(),
        ];
    }
}
