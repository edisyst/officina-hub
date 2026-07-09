<?php

namespace Database\Factories;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimentoMagazzinoFactory extends Factory
{
    protected $model = MovimentoMagazzino::class;

    public function definition(): array
    {
        return [
            'articolo_id'        => Articolo::factory(),
            'tipo'               => TipoMovimento::Carico,
            'quantita'           => $this->faker->numberBetween(1, 10),
            'giacenza_precedente' => 0,
            'giacenza_successiva' => $this->faker->numberBetween(1, 10),
            'prezzo_unitario'    => $this->faker->randomFloat(2, 1, 100),
            'user_id'            => \App\Models\User::factory(),
            'created_at'         => now(),
        ];
    }
}
