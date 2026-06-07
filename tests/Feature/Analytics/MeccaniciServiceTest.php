<?php

namespace Tests\Feature\Analytics;

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
use App\Services\Analytics\MeccaniciService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MeccaniciServiceTest extends TestCase
{
    use RefreshDatabase;

    private MeccaniciService $service;
    private User $meccanico;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->service = app(MeccaniciService::class);

        $this->meccanico = User::factory()->create(['costo_orario' => 25.00]);
        $this->meccanico->assignRole('meccanico');

        $this->cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Test',
            'cognome' => 'Meccanici',
        ]);

        $this->veicolo = Veicolo::create(['marca' => 'Ford', 'modello' => 'Focus']);
    }

    public function test_produttivita_calcola_ore_e_ricavi_correttamente(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $commessa = Commessa::create([
            'numero'              => 'C-MEC-001',
            'stato'               => StatoCommessa::Completata,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'data_consegna'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $this->meccanico->id,
        ]);

        // 3 ore di manodopera a €60/h = €180 ricavo
        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Cambio freni',
            'quantita'           => 3,
            'prezzo_unitario'    => 60,
            'prezzo_acquisto'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        // 4 ore effettive lavorate = 240 minuti
        Lavorazione::create([
            'commessa_id'         => $commessa->id,
            'user_id'             => $this->meccanico->id,
            'descrizione'         => 'Cambio freni',
            'minuti_preventivati' => 180,
            'started_at'          => now()->subMinutes(240),
            'stopped_at'          => now(),
            'minuti_effettivi'    => 240,
        ]);

        $risultati = $this->service->produttivita($da, $a);

        $this->assertCount(1, $risultati);
        $m = $risultati[0];

        $this->assertEquals($this->meccanico->name, $m['nome']);
        $this->assertEquals(4.0, $m['ore_lavorate']);     // 240/60
        $this->assertEquals(3.0, $m['ore_fatturate']);    // riga manodopera quantita=3
        $this->assertEquals(75.0, $m['efficienza']);      // 3/4 * 100
        $this->assertEquals(180.00, $m['ricavo_generato']); // 3 * 60
        $this->assertEquals(100.00, $m['costo']);         // 4h * €25/h
        $this->assertEquals(80.00, $m['margine']);        // 180 - 100
    }

    public function test_efficienza_zero_senza_lavorazioni(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $risultati = $this->service->produttivita($da, $a);

        $this->assertCount(1, $risultati);
        $m = $risultati[0];

        $this->assertEquals(0.0, $m['ore_lavorate']);
        $this->assertEquals(0.0, $m['efficienza']);
        $this->assertEquals(0.0, $m['costo']);
        $this->assertEquals(0.0, $m['margine']);
    }

    public function test_grafico_ritorna_labels_e_dataset(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $grafico = $this->service->grafico($da, $a);

        $this->assertArrayHasKey('labels', $grafico);
        $this->assertArrayHasKey('lavorate', $grafico);
        $this->assertArrayHasKey('fatturate', $grafico);
        $this->assertCount(1, $grafico['labels']);
    }
}
