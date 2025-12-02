<?php

namespace Database\Factories;

use App\Models\Ukm;
use Illuminate\Database\Eloquent\Factories\Factory;

class UkmFactory extends Factory
{
    protected $model = Ukm::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->company(),
            'deskripsi' => $this->faker->optional()->sentence(8),
            'is_active' => true,
        ];
    }
}
