<?php

namespace Database\Factories;

use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommessaFactory extends Factory
{
    protected $model = Commessa::class;

    public function definition(): array
    {
        static $counter = 1000;

        return [
            'numero'               => 'OdL-' . (++$counter),
            'cliente_id'           => Cliente::factory(),
            'veicolo_id'           => Veicolo::factory(),
            'user_id'              => User::factory(),
            'tipo'                 => TipoCommessa::Meccanica,
            'stato'                => StatoCommessa::Bozza,
            'data_ingresso'        => now(),
            'descrizione_cliente'  => 'Manutenzione ordinaria.',
        ];
    }
}
