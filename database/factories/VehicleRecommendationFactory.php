<?php

namespace Database\Factories;

use App\Models\VehicleRecommendation;
use App\Models\Veicolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleRecommendationFactory extends Factory
{
    protected $model = VehicleRecommendation::class;

    public function definition(): array
    {
        return [
            'vehicle_id'             => Veicolo::factory(),
            'origin_work_order_id'   => null,
            'resolved_work_order_id' => null,
            'source'                 => $this->faker->randomElement(['declined', 'deadline', 'mileage']),
            'title'                  => $this->faker->words(3, true),
            'description'            => $this->faker->optional()->sentence(),
            'due_date'               => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'due_km'                 => $this->faker->optional()->numberBetween(50000, 200000),
            'status'                 => 'pending',
            'dismissed_reason'       => null,
        ];
    }
}
