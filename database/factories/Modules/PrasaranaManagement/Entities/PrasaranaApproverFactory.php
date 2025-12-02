<?php

namespace Database\Factories\Modules\PrasaranaManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;

class PrasaranaApproverFactory extends Factory
{
    protected $model = PrasaranaApprover::class;

    public function definition(): array
    {
        return [
            'prasarana_id' => Prasarana::factory(),
            'approver_id' => User::factory(),
            'approval_level' => $this->faker->numberBetween(1, 3),
            'is_active' => true,
        ];
    }
}
