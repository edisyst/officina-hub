<?php

namespace Database\Factories;

use App\Models\TariffaOraria;
use Illuminate\Database\Eloquent\Factories\Factory;

class TariffaOrariaFactory extends Factory
{
    protected $model = TariffaOraria::class;

    public function definition(): array
    {
        return [
            'nome'           => $this->faker->randomElement(['Meccanica', 'Elettrauto', 'Carrozzeria', 'Gommista']),
            'tariffa_oraria' => $this->faker->randomFloat(2, 50, 120),
            'is_default'     => false,
            'is_attiva'      => true,
        ];
    }
}
