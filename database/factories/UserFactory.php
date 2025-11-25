<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userType = fake()->randomElement(['mahasiswa', 'staff']);

        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'bio' => fake()->optional()->sentence(),
            'user_type' => $userType,
            'status' => 'active',
            'role_id' => null,
            'profile_completed' => false,
            'profile_completed_at' => null,
            'blocked_until' => null,
            'blocked_reason' => null,
            'sso_id' => null,
            'sso_provider' => 'poliwangi',
            'sso_data' => null,
            'last_sso_login' => null,
            'last_login_at' => null,
            'password_changed_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'login_count' => 0,
            'last_activity_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'mahasiswa',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'staff',
        ]);
    }
}
