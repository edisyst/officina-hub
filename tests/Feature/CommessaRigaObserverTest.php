<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommessaRigaObserverTest extends TestCase
{
    use RefreshDatabase;

    private function creaCommessa(): Commessa
    {
        $this->seed(RuoliSeeder::class);
        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Obs', 'cognome' => 'Test']);
        $veicolo = Veicolo::create(['marca' => 'Bmw', 'modello' => 'X1']);
        $user    = User::factory()->create();

        return Commessa::create([
            'numero'              => 'OBS-001',
            'stato'               => StatoCommessa::Bozza,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $user->id,
        ]);
    }

    public function test_prezzo_acquisto_copiato_da_articolo_se_nullo(): void
    {
        $commessa = $this->creaCommessa();

        $articolo = Articolo::create([
            'codice'           => 'ART-001',
            'descrizione'      => 'Filtro olio',
            'prezzo_acquisto'  => 12.50,
            'prezzo_vendita'   => 25.00,
            'iva_percentuale'  => 22,
            'giacenza_attuale' => 10,
        ]);

        $riga = CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Filtro olio',
            'articolo_id'        => $articolo->id,
            'quantita'           => 1,
            'prezzo_unitario'    => 25,
            'prezzo_acquisto'    => 0, // sarà sovrascritto
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $this->assertEquals('12.50', $riga->fresh()->prezzo_acquisto);
    }

    public function test_prezzo_acquisto_manuale_non_sovrascritto(): void
    {
        $commessa = $this->creaCommessa();

        $articolo = Articolo::create([
            'codice'           => 'ART-002',
            'descrizione'      => 'Candela',
            'prezzo_acquisto'  => 5.00,
            'prezzo_vendita'   => 10.00,
            'iva_percentuale'  => 22,
            'giacenza_attuale' => 5,
        ]);

        $riga = CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Candela',
            'articolo_id'        => $articolo->id,
            'quantita'           => 1,
            'prezzo_unitario'    => 10,
            'prezzo_acquisto'    => 7.00, // prezzo manuale — non deve cambiare
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $this->assertEquals('7.00', $riga->fresh()->prezzo_acquisto);
    }

    public function test_manodopera_non_tocca_prezzo_acquisto(): void
    {
        $commessa = $this->creaCommessa();

        $riga = CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Lavoro',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $this->assertEquals('0.00', $riga->fresh()->prezzo_acquisto);
    }
}
