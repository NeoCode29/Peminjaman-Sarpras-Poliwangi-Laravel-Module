<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $category = $this->faker->randomElement([
            'user', 'sarpras', 'peminjaman', 'marking', 'report', 'log', 'notification', 'system', 'permission', 'role',
        ]);
        $action = $this->faker->randomElement([
            'view', 'create', 'edit', 'delete', 'block', 'unblock', 'approve', 'reject', 'monitor', 'export',
        ]);
        $suffix = $this->faker->unique()->numerify('####');

        return [
            'name' => sprintf('%s.%s_%s', $category, $action, $suffix),
            'guard_name' => 'web',
            'display_name' => ucfirst(str_replace('_', ' ', $action)),
            'description' => $this->faker->sentence(),
            'category' => $category,
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
