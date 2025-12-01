<?php

namespace Modules\SaranaManagement\Database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;

class SaranaApproverFactory extends Factory
{
    protected $model = SaranaApprover::class;

    public function definition(): array
    {
        return [
            'sarana_id' => Sarana::factory(),
            'approver_id' => User::factory(),
            'approval_level' => $this->faker->numberBetween(1, 5),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
