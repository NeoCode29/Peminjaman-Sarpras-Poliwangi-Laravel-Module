<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = Str::lower($this->faker->unique()->lexify('role_????'));

        return [
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => Str::headline($name),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement([
                'user', 'sarpras', 'peminjaman', 'report', 'system',
            ]),
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
