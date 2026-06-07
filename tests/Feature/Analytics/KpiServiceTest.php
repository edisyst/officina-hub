<?php

namespace Tests\Feature\Analytics;

use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimento;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\CategoriaArticolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Documento;
use App\Models\Lavorazione;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\Analytics\KpiService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class KpiServiceTest extends TestCase
{
    use RefreshDatabase;

    private KpiService $service;
    private User $admin;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // evita collisioni di cache tra test dello stesso periodo
        $this->seed(RuoliSeeder::class);
        $this->service = app(KpiService::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Test',
            'cognome' => 'KPI',
        ]);

        $this->veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Punto']);
    }

    public function test_fatturato_periodo_somma_solo_fatture_non_annullate(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        // Fattura valida
        Documento::create([
            'tipo'           => TipoDocumento::Fattura,
            'stato'          => StatoDocumento::Emessa,
            'numero'         => 'FT-2026-001',
            'anno'           => 2026,
            'progressivo'    => 1,
            'cliente_id'     => $this->cliente->id,
            'data_emissione' => Carbon::now()->toDateString(),
            'imponibile'     => 1000.00,
            'iva_totale'     => 220.00,
            'totale'         => 1220.00,
        ]);

        // Fattura annullata — non deve contare
        Documento::create([
            'tipo'           => TipoDocumento::Fattura,
            'stato'          => StatoDocumento::Annullata,
            'numero'         => 'FT-2026-002',
            'anno'           => 2026,
            'progressivo'    => 2,
            'cliente_id'     => $this->cliente->id,
            'data_emissione' => Carbon::now()->toDateString(),
            'imponibile'     => 500.00,
            'iva_totale'     => 110.00,
            'totale'         => 610.00,
        ]);

        // Nota di credito — non deve contare
        Documento::create([
            'tipo'           => TipoDocumento::NotaCredito,
            'stato'          => StatoDocumento::Emessa,
            'numero'         => 'NC-2026-001',
            'anno'           => 2026,
            'progressivo'    => 1,
            'cliente_id'     => $this->cliente->id,
            'data_emissione' => Carbon::now()->toDateString(),
            'imponibile'     => 200.00,
            'iva_totale'     => 44.00,
            'totale'         => 244.00,
        ]);

        $fatturato = $this->service->fatturatoPeriodo($da, $a);

        $this->assertEquals(1220.00, $fatturato);
    }

    public function test_ticket_medio_corretto(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        foreach ([100.00, 200.00, 300.00] as $i => $totale) {
            Documento::create([
                'tipo'           => TipoDocumento::Fattura,
                'stato'          => StatoDocumento::Emessa,
                'numero'         => "FT-TEST-{$i}",
                'anno'           => 2026,
                'progressivo'    => $i + 1,
                'cliente_id'     => $this->cliente->id,
                'data_emissione' => Carbon::now()->toDateString(),
                'imponibile'     => $totale / 1.22,
                'iva_totale'     => $totale - ($totale / 1.22),
                'totale'         => $totale,
            ]);
        }

        $ticket = $this->service->ticketMedio($da, $a);

        // (100 + 200 + 300) / 3 = 200
        $this->assertEquals(200.00, $ticket);
    }

    public function test_ticket_medio_zero_se_no_fatture(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $ticket = $this->service->ticketMedio($da, $a);

        $this->assertEquals(0.0, $ticket);
    }

    public function test_commesse_aperte_conta_stati_corretti(): void
    {
        $stati = [
            StatoCommessa::Accettata,
            StatoCommessa::InLavorazione,
            StatoCommessa::Sospesa,
        ];

        foreach ($stati as $stato) {
            Commessa::create([
                'numero'              => 'C-' . $stato->value,
                'stato'               => $stato,
                'tipo'                => TipoCommessa::Meccanica,
                'data_ingresso'       => now(),
                'descrizione_cliente' => '',
                'cliente_id'          => $this->cliente->id,
                'veicolo_id'          => $this->veicolo->id,
                'user_id'             => $this->admin->id,
            ]);
        }

        // Commessa completata — non deve contare come aperta
        Commessa::create([
            'numero'              => 'C-completata',
            'stato'               => StatoCommessa::Completata,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $this->admin->id,
        ]);

        $result = $this->service->commesseAperte();

        $this->assertEquals(3, $result['totale']);
        $this->assertEquals(3, $result['meccanica']);
        $this->assertEquals(0, $result['carrozzeria']);
    }

    public function test_ore_periodo_calcola_efficienza(): void
    {
        $da = Carbon::now()->startOfMonth();
        $a  = Carbon::now()->endOfDay();

        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $commessa = Commessa::create([
            'numero'              => 'C-ORE-001',
            'stato'               => StatoCommessa::Completata,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'data_consegna'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $meccanico->id,
        ]);

        // 4 ore fatturabili
        CommessaRiga::create([
            'commessa_id'        => $commessa->id,
            'tipo'               => TipoRiga::Manodopera,
            'descrizione'        => 'Manodopera',
            'quantita'           => 4,
            'prezzo_unitario'    => 50,
            'prezzo_acquisto'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
        ]);

        // 5 ore lavorate = 300 minuti
        Lavorazione::create([
            'commessa_id'         => $commessa->id,
            'user_id'             => $meccanico->id,
            'descrizione'         => 'Lavorazione',
            'minuti_preventivati' => 240,
            'started_at'          => now()->subMinutes(300),
            'stopped_at'          => now(),
            'minuti_effettivi'    => 300,
        ]);

        $result = $this->service->orePeriodo($da, $a);

        $this->assertEquals(4.0, $result['ore_fatturabili']);
        $this->assertEquals(5.0, $result['ore_lavorate']);
        // Efficienza: 4/5 * 100 = 80%
        $this->assertEquals(80.0, $result['efficienza']);
    }

    public function test_sparkline_ritorna_12_valori(): void
    {
        $sparkline = $this->service->sparklineFatturato();

        $this->assertCount(12, $sparkline);
        foreach ($sparkline as $v) {
            $this->assertIsFloat($v);
        }
    }

    public function test_grafico_fatturato_ritorna_struttura_corretta(): void
    {
        $grafico = $this->service->graficoFatturato();

        $this->assertArrayHasKey('labels', $grafico);
        $this->assertArrayHasKey('valori', $grafico);
        $this->assertArrayHasKey('colori', $grafico);
        $this->assertCount(12, $grafico['labels']);
        $this->assertCount(12, $grafico['valori']);
    }

    public function test_distribuzione_commesse_ritorna_labels_e_valori(): void
    {
        Commessa::create([
            'numero'              => 'C-DIST-001',
            'stato'               => StatoCommessa::InLavorazione,
            'tipo'                => TipoCommessa::Meccanica,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $this->admin->id,
        ]);

        $dati = $this->service->distribuzioneCommesse();

        $this->assertArrayHasKey('labels', $dati);
        $this->assertArrayHasKey('valori', $dati);
        $this->assertNotEmpty($dati['labels']);
    }
}
