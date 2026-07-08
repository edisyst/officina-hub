<?php

namespace Database\Factories;

use App\Models\MatricePrezzo;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatricePrezzoFactory extends Factory
{
    protected $model = MatricePrezzo::class;

    public function definition(): array
    {
        return [
            'nome'       => $this->faker->words(2, true),
            'is_default' => false,
            'is_attiva'  => true,
        ];
    }

    public function withScaglioni(): static
    {
        return $this->afterCreating(function (MatricePrezzo $m) {
            $m->scaglioni()->createMany([
                ['costo_da' =>  0.00, 'costo_a' => 50.00, 'markup_percent' => 80.00, 'arrotondamento' => 'none'],
                ['costo_da' => 50.00, 'costo_a' =>  null, 'markup_percent' => 40.00, 'arrotondamento' => '0.50'],
            ]);
        });
    }
}
