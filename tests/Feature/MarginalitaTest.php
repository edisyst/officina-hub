<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\TipoRiga;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Lavorazione;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\MarginalitaService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarginalitaTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcolo_marginalita_corretto(): void
    {
        $this->seed(RuoliSeeder::class);

        $meccanico = User::factory()->create(['costo_orario' => 20.00]);
        $meccanico->assignRole('meccanico');

        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Test', 'cognome' => 'Margine']);
        $veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Punto']);

        $commessa = Commessa::create([
            'numero'              => 'C-TEST-001',
            'stato'               => StatoCommessa::InLavorazione,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $meccanico->id,
        ]);

        // Riga manodopera: 2 ore × €50 = €100 ricavo
        CommessaRiga::create([
            'commessa_id'       => $commessa->id,
            'tipo'              => TipoRiga::Manodopera,
            'descrizione'       => 'Manodopera cambio olio',
            'quantita'          => 2,
            'prezzo_unitario'   => 50,
            'prezzo_acquisto'   => 0,
            'sconto_percentuale'=> 0,
            'iva_percentuale'   => 22,
            'ordinamento'       => 1,
        ]);

        // Riga articolo: 1 pz × €30 vendita, €15 acquisto
        CommessaRiga::create([
            'commessa_id'       => $commessa->id,
            'tipo'              => TipoRiga::Articolo,
            'descrizione'       => 'Filtro olio',
            'quantita'          => 1,
            'prezzo_unitario'   => 30,
            'prezzo_acquisto'   => 15,
            'sconto_percentuale'=> 0,
            'iva_percentuale'   => 22,
            'ordinamento'       => 2,
        ]);

        // Lavorazione: 60 minuti effettivi × €20/h = €20 costo
        Lavorazione::create([
            'commessa_id'        => $commessa->id,
            'user_id'            => $meccanico->id,
            'descrizione'        => 'Cambio olio',
            'minuti_preventivati'=> 60,
            'started_at'         => now()->subHour(),
            'stopped_at'         => now(),
            'minuti_effettivi'   => 60,
        ]);

        $service = app(MarginalitaService::class);
        $result  = $service->calcola($commessa);

        // Ricavi: manodopera €100 + articoli €30 = €130
        $this->assertEquals(100.00, $result['ricavo_manodopera']);
        $this->assertEquals(30.00,  $result['ricavo_articoli']);
        $this->assertEquals(130.00, $result['ricavo_totale']);

        // Costi: manodopera 1h × €20 = €20, articoli 1×€15 = €15
        $this->assertEquals(20.00, $result['costo_manodopera']);
        $this->assertEquals(15.00, $result['costo_articoli']);
        $this->assertEquals(35.00, $result['costo_totale']);

        // Margine: €130 - €35 = €95
        $this->assertEquals(95.00, $result['margine_lordo']);

        // Percentuale: 95/130*100 ≈ 73.1%
        $this->assertEqualsWithDelta(73.1, $result['percentuale_margine'], 0.2);
    }

    public function test_marginalita_senza_lavorazioni_ha_costo_zero(): void
    {
        $this->seed(RuoliSeeder::class);
        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Test2', 'cognome' => 'Zero']);
        $veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Panda']);

        $commessa = Commessa::create([
            'numero'              => 'C-TEST-002',
            'stato'               => StatoCommessa::Bozza,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $meccanico->id,
        ]);

        $result = app(MarginalitaService::class)->calcola($commessa);

        $this->assertEquals(0.0, $result['costo_manodopera']);
        $this->assertEquals(0.0, $result['costo_totale']);
        $this->assertEquals(0.0, $result['percentuale_margine']);
    }
}
