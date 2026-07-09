<?php

namespace Database\Factories;

use App\Enums\UnitaMisura;
use App\Models\Articolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticoloFactory extends Factory
{
    protected $model = Articolo::class;

    public function definition(): array
    {
        return [
            'codice'           => strtoupper($this->faker->bothify('ART-####')),
            'descrizione'      => $this->faker->words(3, true),
            'unita_misura'     => UnitaMisura::Pezzo,
            'prezzo_acquisto'  => $this->faker->randomFloat(2, 1, 100),
            'prezzo_vendita'   => $this->faker->randomFloat(2, 5, 200),
            'iva_percentuale'  => 22.00,
            'giacenza_attuale' => $this->faker->numberBetween(0, 50),
            'scorta_minima'    => 0,
            'attivo'           => true,
        ];
    }
}
