<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $clienti  = Cliente::inRandomOrder()->limit(5)->get();
        $operatore = User::first();

        foreach ($clienti as $cliente) {
            // Telefonate
            Communication::factory()->forCustomer($cliente)->phone()->count(3)->create([
                'user_id'   => $operatore?->id,
                'direction' => 'inbound',
                'body'      => 'Il cliente chiama per aggiornamento stato veicolo.',
            ]);

            // Note interne
            Communication::factory()->forCustomer($cliente)->note()->count(2)->create([
                'user_id' => $operatore?->id,
                'body'    => 'Nota interna: contattato per preventivo gomme.',
            ]);

            // Comunicazioni legate a commesse
            $commesse = $cliente->commesse()->limit(2)->get();
            foreach ($commesse as $commessa) {
                Communication::factory()->forWorkOrder($commessa)->count(2)->create([
                    'user_id' => $operatore?->id,
                ]);
            }
        }
    }
}
