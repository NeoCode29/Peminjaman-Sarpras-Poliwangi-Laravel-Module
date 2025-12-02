<?php

namespace Database\Factories\Modules\PrasaranaManagement\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;

class KategoriPrasaranaFactory extends Factory
{
    protected $model = KategoriPrasarana::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->sentence(6),
            'icon' => $this->faker->optional()->randomElement(['building-office', 'building-library', 'building-storefront']),
            'is_active' => true,
        ];
    }
}
