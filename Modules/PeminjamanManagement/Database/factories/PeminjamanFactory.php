<?php

namespace Modules\PeminjamanManagement\Database\factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PeminjamanManagement\Entities\Peminjaman;

class PeminjamanFactory extends Factory
{
    protected $model = Peminjaman::class;

    public function definition(): array
    {
        $startDate = Carbon::today()->addDays(1);
        $endDate = (clone $startDate)->addDays(2);

        return [
            'user_id' => User::factory(),
            'prasarana_id' => null,
            'lokasi_custom' => $this->faker->optional()->address(),
            'jumlah_peserta' => $this->faker->optional()->numberBetween(10, 100),
            'ukm_id' => null,
            'event_name' => $this->faker->sentence(3),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $this->faker->optional()->time('H:i'),
            'end_time' => $this->faker->optional()->time('H:i'),
            'status' => Peminjaman::STATUS_PENDING,
            'konflik' => null,
            'surat_path' => null,
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'pickup_validated_by' => null,
            'pickup_validated_at' => null,
            'return_validated_by' => null,
            'return_validated_at' => null,
            'cancelled_by' => null,
            'cancelled_reason' => null,
            'cancelled_at' => null,
            'foto_pickup_path' => null,
            'foto_return_path' => null,
        ];
    }

    /**
     * State: pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_PENDING,
        ]);
    }

    /**
     * State: approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * State: rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_REJECTED,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * State: picked_up
     */
    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_PICKED_UP,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDay(),
            'pickup_validated_by' => User::factory(),
            'pickup_validated_at' => now(),
        ]);
    }

    /**
     * State: returned
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_RETURNED,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(3),
            'pickup_validated_by' => User::factory(),
            'pickup_validated_at' => now()->subDays(2),
            'return_validated_by' => User::factory(),
            'return_validated_at' => now(),
        ]);
    }

    /**
     * State: cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Peminjaman::STATUS_CANCELLED,
            'cancelled_by' => User::factory(),
            'cancelled_reason' => $this->faker->sentence(),
            'cancelled_at' => now(),
        ]);
    }

    /**
     * State: with konflik
     */
    public function withKonflik(string $konflikCode = null): static
    {
        return $this->state(fn (array $attributes) => [
            'konflik' => $konflikCode ?? 'KNF-' . strtoupper($this->faker->bothify('??????????')),
        ]);
    }
}
