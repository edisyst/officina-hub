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
use App\Services\Analytics\MarginalitaService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarginalitaAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private MarginalitaService $service;
    private User $meccanico;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->service = app(MarginalitaService::class);

        $this->meccanico = User::factory()->create(['costo_orario' => 20.00]);
        $this->meccanico->assignRole('meccanico');

        $this->cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
        ]);

        $this->veicolo = Veicolo::create(['marca' => 'Alfa Romeo', 'modello' => 'Giulia']);
    }

    private function creaCommessa(string $numero, TipoCommessa $tipo = TipoCommessa::Meccanica): Commessa
    {
        return Commessa::create([
            'numero'              => $numero,
            'stato'               => StatoCommessa::Completata,
            'tipo'                => $tipo,
            'data_ingresso'       => now()->subDays(5),
            'data_consegna'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $this->meccanico->id,
        ]);
    }

    public function test_calcola_singola_commessa_mantenuto(): void
    {
        $commessa = $this->creaCommessa('C-ANALYT-001');

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Revisione',
            'quantita'           => 2,
            'prezzo_unitario'    => 60,
            'prezzo_acquisto'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        Lavorazione::create([
            'commessa_id'         => $commessa->id,
            'user_id'             => $this->meccanico->id,
            'descrizione'         => 'Lavoro',
            'minuti_preventivati' => 120,
            'started_at'          => now()->subHours(2),
            'stopped_at'          => now(),
            'minuti_effettivi'    => 120,
        ]);

        $result = $this->service->calcola($commessa);

        $this->assertEquals(120.00, $result['ricavo_manodopera']); // 2 * 60
        $this->assertEquals(40.00,  $result['costo_manodopera']);  // 2h * €20
        $this->assertEquals(80.00,  $result['margine_lordo']);
    }

    public function test_calcola_per_categoria_raggruppa_per_tipo(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $commMecc = $this->creaCommessa('C-CAT-MECC', TipoCommessa::Meccanica);
        $commCarr = $this->creaCommessa('C-CAT-CARR', TipoCommessa::Carrozzeria);

        foreach ([$commMecc, $commCarr] as $commessa) {
            CommessaRiga::create([
                'commessa_id'        => $commessa->id,
                'tipo'               => TipoRiga::Manodopera,
                'descrizione'        => 'Lavoro',
                'quantita'           => 1,
                'prezzo_unitario'    => 100,
                'prezzo_acquisto'    => 0,
                'sconto_percentuale' => 0,
                'iva_percentuale'    => 22,
                'ordinamento'        => 1,
            ]);
        }

        $risultati = $this->service->calcolaPerCategoria($da, $a);

        $this->assertNotEmpty($risultati);
        $tipi = array_column($risultati, 'tipo');
        $this->assertContains(TipoCommessa::Meccanica->value, $tipi);
        $this->assertContains(TipoCommessa::Carrozzeria->value, $tipi);
    }

    public function test_calcola_per_articoli_ritorna_array(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $commessa = $this->creaCommessa('C-ART-001');

        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Articolo,
            'descrizione'        => 'Filtro olio',
            'quantita'           => 2,
            'prezzo_unitario'    => 15,
            'prezzo_acquisto'    => 8,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        $risultati = $this->service->calcolaPerArticoli($da, $a);

        $this->assertNotEmpty($risultati);
        $this->assertEquals(30.0,  (float) $risultati[0]->ricavo);  // 2 * 15
        $this->assertEquals(16.0,  (float) $risultati[0]->costo);   // 2 * 8
        $this->assertEquals(14.0,  (float) $risultati[0]->margine); // 30 - 16
    }

    public function test_trend_mensile_ritorna_struttura_corretta(): void
    {
        $trend = $this->service->calcolaTrendMensile(6);

        $this->assertArrayHasKey('labels', $trend);
        $this->assertArrayHasKey('datasets', $trend);
        $this->assertCount(6, $trend['labels']);
        $this->assertNotEmpty($trend['datasets']);
    }
}
