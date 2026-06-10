<?php

namespace Tests\Feature;

use App\Actions\Fatturazione\GeneraFatturaAction;
use App\Enums\ContoPrimaNota;
use App\Enums\FormatoExportContabile;
use App\Enums\MetodoPagamento;
use App\Enums\MetodoPrimaNota;
use App\Enums\StatoCommessa;
use App\Enums\TipoPrimaNota;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Documento;
use App\Models\Pagamento;
use App\Models\PrimaNota;
use App\Models\RegistroIva;
use App\Models\Setting;
use App\Models\User;
use App\Models\Veicolo;
use App\Observers\PagamentoObserver;
use App\Services\Export\CsvGenericoFormatter;
use App\Services\Export\TeamSystemFormatter;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContabilitaStep14Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Cliente $cliente;
    protected Documento $documento;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'      => 'fisica',
            'nome'      => 'Mario',
            'cognome'   => 'Rossi',
            'email'     => 'mario@test.it',
            'indirizzo' => 'Via Test 1',
            'citta'     => 'Roma',
            'cap'       => '00100',
            'provincia' => 'RM',
        ]);

        Setting::firstOrCreate(['key' => 'iva_default'], ['value' => '22']);

        // Crea un documento direttamente per i test pagamenti
        $this->documento = Documento::create([
            'numero'           => 'FT-2026-0001',
            'tipo'             => 'fattura',
            'stato'            => 'emessa',
            'cliente_id'       => $this->cliente->id,
            'data_emissione'   => now()->toDateString(),
            'anno'             => now()->year,
            'progressivo'      => 1,
            'totale_documento' => '122.00',
            'imponibile_totale' => '100.00',
            'iva_totale'       => '22.00',
            'user_id'          => $this->admin->id,
        ]);
    }

    /** --- Observer prima nota --- */

    public function test_pagamento_crea_prima_nota_automatica(): void
    {
        $pagamento = Pagamento::create([
            'documento_id'   => $this->documento->id,
            'data_pagamento' => now()->toDateString(),
            'importo'        => '122.00',
            'metodo'         => MetodoPagamento::Contanti->value,
            'user_id'        => $this->admin->id,
        ]);

        $primaNota = PrimaNota::where('pagamento_id', $pagamento->id)->first();

        $this->assertNotNull($primaNota, 'Il record prima nota deve essere creato automaticamente.');
        $this->assertEquals(TipoPrimaNota::Entrata, $primaNota->tipo);
        $this->assertEquals('122.00', $primaNota->importo);
        $this->assertTrue($primaNota->automatico);
        $this->assertEquals(MetodoPrimaNota::Contanti, $primaNota->metodo);
        $this->assertEquals(ContoPrimaNota::Cassa, $primaNota->conto);
    }

    public function test_pagamento_bonifico_assegna_conto_banca(): void
    {
        $pagamento = Pagamento::create([
            'documento_id'   => $this->documento->id,
            'data_pagamento' => now()->toDateString(),
            'importo'        => '100.00',
            'metodo'         => MetodoPagamento::Bonifico->value,
            'user_id'        => $this->admin->id,
        ]);

        $primaNota = PrimaNota::where('pagamento_id', $pagamento->id)->first();

        $this->assertEquals(ContoPrimaNota::Banca, $primaNota->conto);
        $this->assertEquals(MetodoPrimaNota::Bonifico, $primaNota->metodo);
    }

    public function test_pagamento_carta_assegna_conto_pos(): void
    {
        $pagamento = Pagamento::create([
            'documento_id'   => $this->documento->id,
            'data_pagamento' => now()->toDateString(),
            'importo'        => '50.00',
            'metodo'         => MetodoPagamento::Carta->value,
            'user_id'        => $this->admin->id,
        ]);

        $primaNota = PrimaNota::where('pagamento_id', $pagamento->id)->first();

        $this->assertEquals(ContoPrimaNota::Pos, $primaNota->conto);
    }

    /** --- Movimenti manuali --- */

    public function test_crea_movimento_manuale(): void
    {
        PrimaNota::create([
            'data'       => now()->toDateString(),
            'causale'    => 'Acquisto materiale di consumo',
            'tipo'       => TipoPrimaNota::Uscita->value,
            'importo'    => '35.00',
            'metodo'     => MetodoPrimaNota::Contanti->value,
            'conto'      => ContoPrimaNota::Cassa->value,
            'automatico' => false,
            'user_id'    => $this->admin->id,
        ]);

        $this->assertDatabaseHas('prima_nota', [
            'causale'    => 'Acquisto materiale di consumo',
            'tipo'       => 'uscita',
            'automatico' => false,
        ]);
    }

    public function test_movimento_manuale_soft_delete(): void
    {
        $movimento = PrimaNota::create([
            'data'       => now()->toDateString(),
            'causale'    => 'Prelievo cassa',
            'tipo'       => TipoPrimaNota::Uscita->value,
            'importo'    => '200.00',
            'metodo'     => MetodoPrimaNota::Contanti->value,
            'conto'      => ContoPrimaNota::Cassa->value,
            'automatico' => false,
            'user_id'    => $this->admin->id,
        ]);

        $movimento->delete();

        $this->assertSoftDeleted('prima_nota', ['id' => $movimento->id]);
        $this->assertNull(PrimaNota::find($movimento->id));
        $this->assertNotNull(PrimaNota::withTrashed()->find($movimento->id));
    }

    /** --- CSV Generico Formatter --- */

    public function test_csv_generico_formatter_genera_output_corretto(): void
    {
        $righe = collect([
            (object)[
                'data_registrazione' => \Carbon\Carbon::parse('2026-01-15'),
                'numero_documento'   => 'FT-2026-0001',
                'cliente_fornitore'  => 'Mario Rossi',
                'partita_iva'        => '01234567890',
                'codice_fiscale'     => null,
                'imponibile'         => '100.00',
                'iva'                => '22.00',
                'totale'             => '122.00',
                'aliquota_iva'       => '22.00',
                'natura_iva'         => null,
            ],
        ]);

        $formatter = new CsvGenericoFormatter();
        $output    = $formatter->formatta($righe);

        $this->assertStringContainsString("\xEF\xBB\xBF", $output, 'Deve contenere BOM UTF-8');
        $this->assertStringContainsString('FT-2026-0001', $output);
        $this->assertStringContainsString('Mario Rossi', $output);
        $this->assertStringContainsString('100,00', $output, 'Importo con virgola decimale');
        $this->assertStringContainsString('15/01/2026', $output, 'Data in formato italiano');
        $this->assertStringContainsString(';', $output, 'Separatore punto e virgola');
    }

    /** --- TeamSystem Formatter --- */

    public function test_teamsystem_formatter_genera_tracciato_fisso(): void
    {
        Setting::firstOrCreate(['key' => 'export_contabile_codice_conto_vendite'], ['value' => '70000']);
        Setting::firstOrCreate(['key' => 'export_contabile_codice_conto_iva_vendite'], ['value' => '26000']);
        Setting::firstOrCreate(['key' => 'export_contabile_codice_conto_clienti'], ['value' => '15000']);

        $righe = collect([
            (object)[
                'data_registrazione' => \Carbon\Carbon::parse('2026-01-15'),
                'numero_documento'   => 'FT-2026-0001',
                'cliente_fornitore'  => 'Mario Rossi',
                'partita_iva'        => '01234567890',
                'imponibile'         => '100.00',
                'iva'                => '22.00',
                'totale'             => '122.00',
                'aliquota_iva'       => '22.00',
            ],
        ]);

        $formatter = new TeamSystemFormatter();
        $output    = $formatter->formatta($righe);

        $righe_output = explode("\r\n", trim($output));
        $this->assertCount(1, $righe_output);

        $riga = $righe_output[0];
        $this->assertStringStartsWith('F ', $riga, 'Tipo record deve essere F per fattura');
        $this->assertStringContainsString('15/01/2026', $riga);
        $this->assertStringContainsString('70000', $riga, 'Deve contenere il conto vendite');
    }

    /** --- Stub formatters --- */

    public function test_zucchetti_formatter_lancia_eccezione(): void
    {
        $this->expectException(\RuntimeException::class);

        $formatter = new \App\Services\Export\ZucchettiFormatter();
        $formatter->formatta(collect());
    }

    public function test_datagamma_formatter_lancia_eccezione(): void
    {
        $this->expectException(\RuntimeException::class);

        $formatter = new \App\Services\Export\DatagammaFormatter();
        $formatter->formatta(collect());
    }

    /** --- Route access --- */

    public function test_routes_accessibili_da_admin(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('contabilita.prima-nota'))->assertOk();
        $this->get(route('contabilita.riepilogo'))->assertOk();
        $this->get(route('contabilita.export-sdi-batch'))->assertOk();
    }

    public function test_routes_non_accessibili_senza_login(): void
    {
        $this->get(route('contabilita.prima-nota'))->assertRedirect(route('login'));
        $this->get(route('contabilita.riepilogo'))->assertRedirect(route('login'));
        $this->get(route('contabilita.export-sdi-batch'))->assertRedirect(route('login'));
    }
}
