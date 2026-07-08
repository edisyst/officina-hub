<?php

namespace Database\Factories;

use App\Enums\Alimentazione;
use App\Enums\TipoVeicolo;
use App\Models\Veicolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class VeicoloFactory extends Factory
{
    protected $model = Veicolo::class;

    public function definition(): array
    {
        return [
            'tipo'   => TipoVeicolo::Auto,
            'targa'  => strtoupper($this->faker->bothify('??###??')),
            'marca'  => $this->faker->randomElement(['Fiat', 'Alfa Romeo', 'Volkswagen', 'Ford']),
            'modello'=> $this->faker->word(),
        ];
    }
}
