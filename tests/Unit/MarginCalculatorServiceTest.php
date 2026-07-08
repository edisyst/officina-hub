<?php

namespace Tests\Unit;

use App\DataTransferObjects\CommessaMargins;
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
use App\Services\Commesse\MarginCalculatorService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarginCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private function creaCommessa(string $numero = 'MC-001'): Commessa
    {
        $this->seed(RuoliSeeder::class);
        $cliente = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Test', 'cognome' => 'Margin']);
        $veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Uno']);
        $user    = User::factory()->create();

        return Commessa::create([
            'numero'              => $numero,
            'stato'               => StatoCommessa::InLavorazione,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $user->id,
        ]);
    }

    public function test_margini_completi(): void
    {
        config(['margins.labor_cost_per_hour' => 25.0]);

        $commessa  = $this->creaCommessa();
        $meccanico = User::factory()->create(['costo_orario' => 20.0]);

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Manodopera',
            'quantita'           => 2,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 0,
            'ore_preventivate'   => 2.5,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Filtro',
            'quantita'           => 2,
            'prezzo_unitario'    => 30,
            'prezzo_acquisto'    => 15,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 2,
        ]);

        Lavorazione::create([
            'commessa_id'        => $commessa->id,
            'user_id'            => $meccanico->id,
            'descrizione'        => 'Lavoro',
            'minuti_preventivati'=> 120,
            'started_at'         => now()->subHours(2),
            'stopped_at'         => now(),
            'minuti_effettivi'   => 90, // 1.5h × €20 = €30 costo
        ]);

        $svc    = app(MarginCalculatorService::class);
        $result = $svc->calcola($commessa);

        $this->assertInstanceOf(CommessaMargins::class, $result);

        // Ricavi: manodopera 2×50=100, ricambi 2×30=60
        $this->assertEquals(100.0, $result->ricavoManodopera);
        $this->assertEquals(60.0, $result->ricavoRicambi);
        $this->assertEquals(160.0, $result->ricavoTotale);

        // Costi ricambi: 2×15=30
        $this->assertEquals(30.0, $result->costoRicambi);
        $this->assertEquals(30.0, $result->margineRicambi);
        $this->assertEquals(50.0, $result->margineRicambiPerc);

        // Costo manodopera: 1.5h × €20 (per-meccanico) = €30
        $this->assertEquals(30.0, $result->costoManodopera);

        // Ore
        $this->assertEquals(2.5, $result->orePreventivate);
        $this->assertEqualsWithDelta(1.5, $result->oreEffettive, 0.01);
        $this->assertEqualsWithDelta(-1.0, $result->deltaOre, 0.01);

        // Margine totale: 160 - (30+30) = 100
        $this->assertEquals(100.0, $result->margineTotale);
        $this->assertEquals(0, $result->righeRicambiSenzaCosto);
        $this->assertEquals(0, $result->righeManodoperaSenzaStima);
    }

    public function test_nulli_parziali_non_falsano_totali(): void
    {
        $commessa = $this->creaCommessa('MC-002');

        // Riga articolo senza prezzo_acquisto
        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Ricambio senza costo',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        // Riga manodopera senza ore_preventivate
        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Manodopera senza stima',
            'quantita'           => 1,
            'prezzo_unitario'    => 40,
            'prezzo_acquisto'    => 0,
            'ore_preventivate'   => null,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 2,
        ]);

        $result = app(MarginCalculatorService::class)->calcola($commessa);

        $this->assertEquals(50.0, $result->ricavoRicambi);
        $this->assertEquals(0.0, $result->costoRicambi);
        $this->assertEquals(1, $result->righeRicambiSenzaCosto);
        $this->assertEquals(0.0, $result->orePreventivate);
        $this->assertEquals(1, $result->righeManodoperaSenzaStima);
    }

    public function test_senza_config_costo_manodopera_e_null(): void
    {
        config(['margins.labor_cost_per_hour' => null]);

        $commessa  = $this->creaCommessa('MC-003');
        $meccanico = User::factory()->create(['costo_orario' => null]);

        Lavorazione::create([
            'commessa_id'        => $commessa->id,
            'user_id'            => $meccanico->id,
            'descrizione'        => 'Lavoro',
            'minuti_preventivati'=> 60,
            'started_at'         => now()->subHour(),
            'stopped_at'         => now(),
            'minuti_effettivi'   => 60,
        ]);

        $result = app(MarginCalculatorService::class)->calcola($commessa);

        $this->assertNull($result->costoManodopera);
        $this->assertEqualsWithDelta(1.0, $result->oreEffettive, 0.01);
    }

    public function test_progress_ore_in_budget_e_out_of_budget(): void
    {
        $commessa = $this->creaCommessa('MC-004');

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Lavoro',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 0,
            'ore_preventivate'   => 1.0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $meccanico = User::factory()->create(['costo_orario' => null]);
        Lavorazione::create([
            'commessa_id'        => $commessa->id,
            'user_id'            => $meccanico->id,
            'descrizione'        => 'Lavoro',
            'minuti_preventivati'=> 60,
            'started_at'         => now()->subHours(2),
            'stopped_at'         => now(),
            'minuti_effettivi'   => 120, // 2h > 1h prevista
        ]);

        $result = app(MarginCalculatorService::class)->calcola($commessa);

        $this->assertFalse($result->oreInBudget());
        $this->assertEquals(200.0, $result->progressoOrePerc());
        $this->assertEqualsWithDelta(1.0, $result->deltaOre, 0.01);
    }
}
