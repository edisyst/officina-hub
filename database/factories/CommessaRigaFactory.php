<?php

namespace Database\Factories;

use App\Enums\TipoRiga;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommessaRigaFactory extends Factory
{
    protected $model = CommessaRiga::class;

    public function definition(): array
    {
        return [
            'commessa_id'        => Commessa::factory(),
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => $this->faker->words(3, true),
            'quantita'           => 1,
            'prezzo_unitario'    => $this->faker->randomFloat(2, 5, 200),
            'prezzo_acquisto'    => $this->faker->randomFloat(2, 1, 100),
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22.00,
            'ordinamento'        => 0,
        ];
    }
}
