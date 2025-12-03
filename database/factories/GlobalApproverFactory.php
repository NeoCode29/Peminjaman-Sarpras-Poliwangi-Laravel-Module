<?php

namespace Database\Factories;

use App\Models\GlobalApprover;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GlobalApprover>
 */
class GlobalApproverFactory extends Factory
{
    protected $model = GlobalApprover::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'approval_level' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the approver is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific approval level.
     */
    public function level(int $level): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_level' => $level,
        ]);
    }

    /**
     * Set as primary approver (level 1).
     */
    public function primary(): static
    {
        return $this->level(1);
    }

    /**
     * Set as secondary approver (level 2).
     */
    public function secondary(): static
    {
        return $this->level(2);
    }

    /**
     * Set as tertiary approver (level 3).
     */
    public function tertiary(): static
    {
        return $this->level(3);
    }
}
