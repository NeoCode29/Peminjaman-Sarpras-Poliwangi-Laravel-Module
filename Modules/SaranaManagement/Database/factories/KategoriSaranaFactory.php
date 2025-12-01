<?php

namespace Modules\SaranaManagement\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SaranaManagement\Entities\KategoriSarana;

class KategoriSaranaFactory extends Factory
{
    protected $model = KategoriSarana::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->words(2, true),
            'deskripsi' => $this->faker->optional()->sentence(),
        ];
    }
}
