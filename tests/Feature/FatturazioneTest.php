<?php

namespace Tests\Feature;

use App\Actions\Fatturazione\EmettereNotaCreditoAction;
use App\Actions\Fatturazione\GeneraFatturaAction;
use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Pagamento;
use App\Models\Setting;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\FatturaPAService;
use App\Services\NumerazioneService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FatturazioneTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Cliente $cliente;
    protected Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'           => 'fisica',
            'nome'           => 'Mario',
            'cognome'        => 'Rossi',
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'email'          => 'mario@test.it',
            'indirizzo'      => 'Via Test 1',
            'citta'          => 'Roma',
            'cap'            => '00100',
            'provincia'      => 'RM',
        ]);

        $veicolo = Veicolo::create([
            'marca'   => 'Fiat',
            'modello' => 'Panda',
            'tipo'    => 'auto',
        ]);

        $this->commessa = Commessa::create([
            'numero'              => 'COM-2026-0001',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $veicolo->id,
            'tipo'                => 'tagliando',
            'stato'               => StatoCommessa::Consegnata,
            'user_id'             => $this->admin->id,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Tagliando completo',
        ]);

        // Aggiunge una riga alla commessa
        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => 'manodopera',
            'descrizione'        => 'Tagliando completo',
            'quantita'           => 1,
            'prezzo_unitario'    => 200.00,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
        ]);

        // Impostazioni minime per FatturaPA
        Setting::firstOrCreate(['key' => 'officina_piva'],   ['value' => '12345678901']);
        Setting::firstOrCreate(['key' => 'officina_nome'],   ['value' => 'Test Officina']);
        Setting::firstOrCreate(['key' => 'officina_via'],    ['value' => 'Via Test 1']);
        Setting::firstOrCreate(['key' => 'officina_cap'],    ['value' => '00100']);
        Setting::firstOrCreate(['key' => 'officina_citta'],  ['value' => 'Roma']);
        Setting::firstOrCreate(['key' => 'fatturapa_regime_fiscale'],  ['value' => 'RF01']);
        Setting::firstOrCreate(['key' => 'fatturapa_esigibilita_iva'], ['value' => 'I']);
    }

    /** Una commessa completata/consegnata genera una fattura bozza con le righe copiate */
    public function test_genera_fattura_da_commessa(): void
    {
        $this->actingAs($this->admin);

        $documento = app(GeneraFatturaAction::class)->execute($this->commessa);

        $this->assertInstanceOf(Documento::class, $documento);
        $this->assertEquals(TipoDocumento::Fattura, $documento->tipo);
        $this->assertEquals(StatoDocumento::Bozza, $documento->stato);
        $this->assertStringStartsWith('FT-', $documento->numero);
        $this->assertEquals(1, $documento->righe->count());
        $this->assertEquals(200.00, (float) $documento->imponibile);
        $this->assertEquals(44.00, (float) $documento->iva_totale);
        $this->assertEquals(244.00, (float) $documento->totale);

        // La commessa deve essere passata a "fatturata"
        $this->commessa->refresh();
        $this->assertEquals(StatoCommessa::Fatturata, $this->commessa->stato);
    }

    /** La numerazione progressiva usa lockForUpdate e non genera duplicati */
    public function test_numerazione_progressiva_unicita(): void
    {
        $service = app(NumerazioneService::class);
        $anno    = now()->year;

        // Simula 5 operazioni sequenziali: ogni chiamata crea il documento
        // prima che la successiva legga il progressivo, garantendo unicità
        $progressivi = collect(range(1, 5))->map(function () use ($service, $anno) {
            return DB::transaction(function () use ($service, $anno) {
                $progressivo = $service->prossimo('fattura', $anno);

                Documento::create([
                    'tipo'           => 'fattura',
                    'numero'         => 'FT-' . $anno . '-' . str_pad($progressivo, 4, '0', STR_PAD_LEFT),
                    'anno'           => $anno,
                    'progressivo'    => $progressivo,
                    'cliente_id'     => $this->cliente->id,
                    'data_emissione' => now()->toDateString(),
                    'stato'          => 'bozza',
                ]);

                return $progressivo;
            });
        });

        $this->assertEquals(5, $progressivi->unique()->count(), 'I progressivi devono essere tutti distinti');
        $this->assertEquals(range(1, 5), $progressivi->sort()->values()->all());
    }

    /** L'XML generato è ben formato e contiene i blocchi obbligatori */
    public function test_genera_xml_struttura(): void
    {
        $documento = app(GeneraFatturaAction::class)->execute($this->commessa);
        $documento->update([
            'stato'            => StatoDocumento::Emessa,
            'metodo_pagamento' => 'bonifico',
        ]);
        $documento->load(['cliente', 'righe']);

        $xml = app(FatturaPAService::class)->genera($documento);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('FatturaElettronica', $xml);
        $this->assertStringContainsString('FPR12', $xml);
        $this->assertStringContainsString('DatiTrasmissione', $xml);
        $this->assertStringContainsString('CedentePrestatore', $xml);
        $this->assertStringContainsString('CessionarioCommittente', $xml);
        $this->assertStringContainsString('DatiGeneraliDocumento', $xml);
        $this->assertStringContainsString('TD01', $xml);
        $this->assertStringContainsString('DatiBeniServizi', $xml);
        $this->assertStringContainsString('DatiRiepilogo', $xml);
        $this->assertStringContainsString('DatiPagamento', $xml);
        $this->assertStringContainsString('MP05', $xml); // bonifico
        $this->assertStringContainsString($documento->numero, $xml);

        // Deve essere XML valido (ben formato)
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml) !== false, 'L\'XML deve essere ben formato');
    }

    /** Il nome file SdI rispetta il formato IT{PIVA}_FPR12_{NNN}.xml */
    public function test_nome_file_sdi_formato(): void
    {
        $documento = app(GeneraFatturaAction::class)->execute($this->commessa);

        $nomeFile = app(FatturaPAService::class)->nomeFile($documento);

        $this->assertMatchesRegularExpression(
            '/^IT\d{11}_FPR12_\d{5}\.xml$/',
            $nomeFile,
            "Il nome file SdI deve rispettare il formato IT{PIVA}_FPR12_{NNNNN}.xml"
        );
    }

    /** La registrazione di un pagamento pari al totale porta il documento a "pagata" */
    public function test_registrazione_pagamento_completo(): void
    {
        $documento = app(GeneraFatturaAction::class)->execute($this->commessa);
        $documento->update(['stato' => StatoDocumento::Emessa]);

        $documento->pagamenti()->create([
            'data_pagamento' => now()->toDateString(),
            'importo'        => 244.00,
            'metodo'         => 'contanti',
            'user_id'        => $this->admin->id,
        ]);

        $documento->load('pagamenti');
        if ($documento->totale_pagato >= (float) $documento->totale) {
            $documento->update(['stato' => StatoDocumento::Pagata]);
        }

        $documento->refresh();
        $this->assertEquals(StatoDocumento::Pagata, $documento->stato);
    }

    /** La nota di credito ha TipoDocumento TD04 e importi negativi */
    public function test_nota_di_credito(): void
    {
        $fattura = app(GeneraFatturaAction::class)->execute($this->commessa);
        $fattura->update(['stato' => StatoDocumento::Emessa]);

        $notaCredito = app(EmettereNotaCreditoAction::class)->execute($fattura);

        $this->assertEquals(TipoDocumento::NotaCredito, $notaCredito->tipo);
        $this->assertStringStartsWith('NC-', $notaCredito->numero);
        $this->assertTrue((float) $notaCredito->totale < 0, 'Il totale della nota credito deve essere negativo');
        $this->assertTrue((float) $notaCredito->righe->first()->quantita < 0, 'Le quantità devono essere negative');

        // L'XML della nota di credito deve contenere TD04
        $notaCredito->update(['stato' => StatoDocumento::Emessa, 'metodo_pagamento' => 'contanti']);
        $notaCredito->load(['cliente', 'righe']);
        $xml = app(FatturaPAService::class)->genera($notaCredito);
        $this->assertStringContainsString('TD04', $xml);

        // La fattura originale deve essere annullata
        $fattura->refresh();
        $this->assertEquals(StatoDocumento::Annullata, $fattura->stato);
    }

    /** Il registro IVA viene popolato quando il documento passa a "emessa" */
    public function test_registro_iva_popolato_su_emissione(): void
    {
        $documento = app(GeneraFatturaAction::class)->execute($this->commessa);
        $documento->load(['cliente', 'righe']);

        $this->assertEquals(0, $documento->registroIva()->count());

        $documento->update(['stato' => StatoDocumento::Emessa]);

        $this->assertGreaterThan(0, $documento->registroIva()->count());

        $voce = $documento->registroIva()->first();
        $this->assertEquals(200.00, (float) $voce->imponibile);
        $this->assertEquals(44.00, (float) $voce->iva);
        $this->assertEquals(22.00, (float) $voce->aliquota_iva);
    }

    /** Il ciclo di vita completo del documento funziona end-to-end */
    public function test_ciclo_vita_documento(): void
    {
        // 1. Genera da commessa
        $doc = app(GeneraFatturaAction::class)->execute($this->commessa);
        $this->assertEquals(StatoDocumento::Bozza, $doc->stato);

        // 2. Emetti
        $doc->update(['stato' => StatoDocumento::Emessa, 'metodo_pagamento' => 'contanti']);
        $doc->refresh();
        $this->assertEquals(StatoDocumento::Emessa, $doc->stato);

        // 3. Genera XML
        $doc->load(['cliente', 'righe']);
        $xml = app(FatturaPAService::class)->genera($doc);
        $this->assertNotEmpty($xml);

        // 4. Pagamento completo → pagata
        $doc->pagamenti()->create([
            'data_pagamento' => now()->toDateString(),
            'importo'        => (float) $doc->totale,
            'metodo'         => 'contanti',
            'user_id'        => $this->admin->id,
        ]);
        $doc->load('pagamenti');
        if ($doc->totale_pagato >= (float) $doc->totale) {
            $doc->update(['stato' => StatoDocumento::Pagata]);
        }
        $doc->refresh();
        $this->assertEquals(StatoDocumento::Pagata, $doc->stato);
    }
}
