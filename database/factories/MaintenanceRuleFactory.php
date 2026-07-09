<?php

namespace Database\Factories;

use App\Models\MaintenanceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceRuleFactory extends Factory
{
    protected $model = MaintenanceRule::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->words(3, true),
            'every_km'     => $this->faker->optional()->numberBetween(5000, 60000),
            'every_months' => $this->faker->optional()->numberBetween(6, 36),
            'is_active'    => true,
        ];
    }
}
