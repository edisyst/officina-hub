<?php

namespace Tests\Feature;

use App\Actions\Acquisti\RicezioneMerceAction;
use App\Enums\MetodoPagamentoFornitore;
use App\Enums\StatoFatturaAcquisto;
use App\Enums\StatoOrdineFornitore;
use App\Enums\TipoPrimaNota;
use App\Models\Articolo;
use App\Models\FatturaAcquisto;
use App\Models\Fornitore;
use App\Models\OrdineFornitore;
use App\Models\OrdineFornitoreRiga;
use App\Models\PagamentoFornitore;
use App\Models\PrimaNota;
use App\Models\RegistroIva;
use App\Models\User;
use App\Services\FatturaPAParser;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AcquistiStep15Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Fornitore $fornitore;
    protected Articolo $articolo;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->fornitore = Fornitore::create([
            'ragione_sociale' => 'Test Fornitore Srl',
            'partita_iva'     => '12345678901',
            'codice_fiscale'  => '12345678901',
            'email'           => 'fornitore@test.it',
            'indirizzo'       => 'Via Test 1',
            'citta'           => 'Milano',
            'cap'             => '20100',
            'provincia'       => 'MI',
        ]);

        $this->articolo = Articolo::create([
            'codice'           => 'ART001',
            'codice_fornitore' => 'CF-001',
            'descrizione'      => 'Articolo di test',
            'fornitore_id'     => $this->fornitore->id,
            'prezzo_acquisto'  => 10.00,
            'prezzo_vendita'   => 15.00,
            'iva_percentuale'  => 22,
            'scorta_minima'    => 5,
            'scorta_massima'   => 20,
            'giacenza_attuale' => 2,
            'attivo'           => true,
        ]);
    }

    /** Sottoscorta: articolo con giacenza < scorta_minima */
    public function test_articolo_sotto_scorta(): void
    {
        $this->assertTrue($this->articolo->isSottoScorta());
    }

    /** Crea ordine fornitore e verifica numerazione */
    public function test_crea_ordine_fornitore(): void
    {
        $ordine = OrdineFornitore::create([
            'numero'      => 'ORD-' . now()->year . '-0001',
            'anno'        => now()->year,
            'progressivo' => 1,
            'fornitore_id' => $this->fornitore->id,
            'stato'        => StatoOrdineFornitore::Bozza,
            'data_ordine'  => today(),
            'user_id'      => $this->admin->id,
        ]);

        OrdineFornitoreRiga::create([
            'ordine_fornitore_id'    => $ordine->id,
            'articolo_id'            => $this->articolo->id,
            'descrizione'            => $this->articolo->descrizione,
            'codice_fornitore'       => $this->articolo->codice_fornitore,
            'quantita_ordinata'      => 10,
            'prezzo_unitario_atteso' => 10.00,
        ]);

        $this->assertDatabaseHas('ordini_fornitori', ['numero' => 'ORD-' . now()->year . '-0001']);
        $this->assertDatabaseHas('ordine_fornitore_righe', ['ordine_fornitore_id' => $ordine->id, 'quantita_ordinata' => 10]);
    }

    /** Genera ordini da 2 fornitori diversi con articoli sottoscorta */
    public function test_genera_ordini_da_due_fornitori(): void
    {
        $fornitore2 = Fornitore::create([
            'ragione_sociale' => 'Secondo Fornitore Srl',
            'partita_iva'     => '98765432109',
            'email'           => 'f2@test.it',
            'indirizzo'       => 'Via 2',
            'citta'           => 'Roma',
            'cap'             => '00100',
            'provincia'       => 'RM',
        ]);

        $articolo2 = Articolo::create([
            'codice'          => 'ART002',
            'descrizione'     => 'Articolo 2',
            'fornitore_id'    => $fornitore2->id,
            'prezzo_acquisto' => 5.00,
            'prezzo_vendita'  => 8.00,
            'iva_percentuale' => 22,
            'scorta_minima'   => 10,
            'scorta_massima'  => 50,
            'giacenza_attuale' => 3,
            'attivo'          => true,
        ]);

        $articoliSottoscorta = Articolo::attivi()->sottoScorta()->whereNotNull('fornitore_id')->get();
        $perFornitore = $articoliSottoscorta->groupBy('fornitore_id');

        $this->assertCount(2, $perFornitore);
    }

    /** Ricezione parziale aggiorna stato a parzialmente_ricevuto */
    public function test_ricezione_parziale(): void
    {
        $ordine = OrdineFornitore::create([
            'numero'       => 'ORD-2026-0001',
            'anno'         => 2026,
            'progressivo'  => 1,
            'fornitore_id' => $this->fornitore->id,
            'stato'        => StatoOrdineFornitore::Confermato,
            'data_ordine'  => today(),
            'user_id'      => $this->admin->id,
        ]);

        $riga = OrdineFornitoreRiga::create([
            'ordine_fornitore_id'    => $ordine->id,
            'articolo_id'            => $this->articolo->id,
            'descrizione'            => $this->articolo->descrizione,
            'quantita_ordinata'      => 10,
            'prezzo_unitario_atteso' => 10.00,
        ]);

        $action = app(RicezioneMerceAction::class);
        $action->execute(
            ordine: $ordine,
            numeroDdt: 'DDT-001',
            dataDdt: today()->toDateString(),
            dataRicezione: today()->toDateString(),
            righe: [['ordine_riga_id' => $riga->id, 'quantita_ricevuta' => 5, 'prezzo_unitario' => 10.00]],
            utente: $this->admin,
        );

        $ordine->refresh();
        $this->assertEquals(StatoOrdineFornitore::ParzialmenteRicevuto, $ordine->stato);

        $this->articolo->refresh();
        $this->assertEquals(7, $this->articolo->giacenza_attuale); // 2 + 5

        $this->assertDatabaseHas('ddt_fornitori', ['numero_ddt' => 'DDT-001']);
    }

    /** Ricezione completa aggiorna stato a ricevuto */
    public function test_ricezione_completa(): void
    {
        $ordine = OrdineFornitore::create([
            'numero'       => 'ORD-2026-0002',
            'anno'         => 2026,
            'progressivo'  => 2,
            'fornitore_id' => $this->fornitore->id,
            'stato'        => StatoOrdineFornitore::Confermato,
            'data_ordine'  => today(),
            'user_id'      => $this->admin->id,
        ]);

        $riga = OrdineFornitoreRiga::create([
            'ordine_fornitore_id'    => $ordine->id,
            'articolo_id'            => $this->articolo->id,
            'descrizione'            => $this->articolo->descrizione,
            'quantita_ordinata'      => 8,
            'prezzo_unitario_atteso' => 10.00,
        ]);

        $action = app(RicezioneMerceAction::class);
        $action->execute(
            ordine: $ordine,
            numeroDdt: 'DDT-002',
            dataDdt: today()->toDateString(),
            dataRicezione: today()->toDateString(),
            righe: [['ordine_riga_id' => $riga->id, 'quantita_ricevuta' => 8, 'prezzo_unitario' => 10.00]],
            utente: $this->admin,
        );

        $ordine->refresh();
        $this->assertEquals(StatoOrdineFornitore::Ricevuto, $ordine->stato);
    }

    /** Pagamento fornitore crea prima nota uscita automaticamente */
    public function test_pagamento_fornitore_crea_prima_nota_uscita(): void
    {
        $fattura = FatturaAcquisto::create([
            'fornitore_id'            => $this->fornitore->id,
            'numero_fattura_fornitore' => 'FT-001',
            'data_fattura'            => today(),
            'data_ricezione'          => today(),
            'imponibile'              => 100.00,
            'iva_totale'              => 22.00,
            'totale'                  => 122.00,
            'stato'                   => StatoFatturaAcquisto::Registrata,
            'user_id'                 => $this->admin->id,
        ]);

        PagamentoFornitore::create([
            'fattura_acquisto_id' => $fattura->id,
            'data_pagamento'      => today(),
            'importo'             => 122.00,
            'metodo'              => MetodoPagamentoFornitore::Bonifico,
            'user_id'             => $this->admin->id,
        ]);

        $this->assertDatabaseHas('prima_nota', [
            'tipo'        => TipoPrimaNota::Uscita->value,
            'importo'     => 122.00,
            'fornitore_id' => $this->fornitore->id,
            'automatico'  => true,
        ]);
    }

    /** Fattura a stato registrata crea record registro IVA acquisti */
    public function test_fattura_registrata_crea_registro_iva_acquisti(): void
    {
        $fattura = FatturaAcquisto::create([
            'fornitore_id'            => $this->fornitore->id,
            'numero_fattura_fornitore' => 'FT-002',
            'data_fattura'            => today(),
            'data_ricezione'          => today(),
            'imponibile'              => 200.00,
            'iva_totale'              => 44.00,
            'totale'                  => 244.00,
            'stato'                   => StatoFatturaAcquisto::Ricevuta,
            'user_id'                 => $this->admin->id,
        ]);

        $fattura->update(['stato' => StatoFatturaAcquisto::Registrata]);

        $this->assertDatabaseHas('registro_iva', [
            'tipo_registro'    => 'acquisti',
            'numero_documento' => 'FT-002',
            'imponibile'       => 200.00,
        ]);
    }

    /** Parser XML FatturaPA estrae dati base */
    public function test_parser_xml_fatturapa(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<FatturaElettronica versione="FPR12"
  xmlns="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2">
  <FatturaElettronicaHeader>
    <DatiTrasmissione>
      <IdTrasmittente><IdPaese>IT</IdPaese><IdCodice>00000000001</IdCodice></IdTrasmittente>
      <ProgressivoInvio>1</ProgressivoInvio>
      <FormatoTrasmissione>FPR12</FormatoTrasmissione>
      <CodiceDestinatario>ABCDEFG</CodiceDestinatario>
    </DatiTrasmissione>
    <CedentePrestatore>
      <DatiAnagrafici>
        <IdFiscaleIVA><IdPaese>IT</IdPaese><IdCodice>12345678901</IdCodice></IdFiscaleIVA>
        <Anagrafica><Denominazione>Test Fornitore Srl</Denominazione></Anagrafica>
      </DatiAnagrafici>
    </CedentePrestatore>
    <CessionarioCommittente>
      <DatiAnagrafici>
        <IdFiscaleIVA><IdPaese>IT</IdPaese><IdCodice>00000000001</IdCodice></IdFiscaleIVA>
        <Anagrafica><Denominazione>Officina Hub</Denominazione></Anagrafica>
      </DatiAnagrafici>
    </CessionarioCommittente>
  </FatturaElettronicaHeader>
  <FatturaElettronicaBody>
    <DatiGenerali>
      <DatiGeneraliDocumento>
        <TipoDocumento>TD01</TipoDocumento>
        <Divisa>EUR</Divisa>
        <Data>2026-06-01</Data>
        <Numero>FT-TEST-001</Numero>
        <ImportoTotaleDocumento>122.00</ImportoTotaleDocumento>
      </DatiGeneraliDocumento>
    </DatiGenerali>
    <DatiBeniServizi>
      <DettaglioLinee>
        <NumeroLinea>1</NumeroLinea>
        <Descrizione>Articolo di test</Descrizione>
        <Quantita>10.00</Quantita>
        <PrezzoUnitario>10.00</PrezzoUnitario>
        <PrezzoTotale>100.00</PrezzoTotale>
        <AliquotaIVA>22.00</AliquotaIVA>
      </DettaglioLinee>
      <DatiRiepilogo>
        <AliquotaIVA>22.00</AliquotaIVA>
        <ImponibileImporto>100.00</ImponibileImporto>
        <Imposta>22.00</Imposta>
      </DatiRiepilogo>
    </DatiBeniServizi>
  </FatturaElettronicaBody>
</FatturaElettronica>
XML;

        $tmpFile = tempnam(sys_get_temp_dir(), 'fattura_test_') . '.xml';
        file_put_contents($tmpFile, $xml);

        $parser    = app(FatturaPAParser::class);
        $risultato = $parser->parsaFatturaAcquisto($tmpFile);

        unlink($tmpFile);

        $this->assertEquals('FT-TEST-001', $risultato['numero_fattura']);
        $this->assertEquals('2026-06-01', $risultato['data_fattura']);
        $this->assertEquals(100.00, $risultato['imponibile']);
        $this->assertEquals(22.00, $risultato['iva_totale']);
        $this->assertEquals(122.00, $risultato['totale']);
        $this->assertEquals($this->fornitore->id, $risultato['fornitore_id']); // matched per P.IVA
        $this->assertFalse($risultato['fornitore_warn']);
        $this->assertCount(1, $risultato['righe']);
        $this->assertEquals('Articolo di test', $risultato['righe'][0]['descrizione']);
    }

    /** Fattura da DDT fine mese: rotta accesso */
    public function test_route_fatture_acquisto_accesso_admin(): void
    {
        $this->actingAs($this->admin)
             ->get(route('acquisti.fatture'))
             ->assertOk();
    }

    /** Rotta ordini fornitore accessibile ad admin */
    public function test_route_ordini_fornitore_accesso_admin(): void
    {
        $this->actingAs($this->admin)
             ->get(route('acquisti.ordini'))
             ->assertOk();
    }

    /** Registro IVA tab acquisti mostra fatture registrate nel mese */
    public function test_registro_iva_acquisti_tab(): void
    {
        $fattura = FatturaAcquisto::create([
            'fornitore_id'            => $this->fornitore->id,
            'numero_fattura_fornitore' => 'FT-003',
            'data_fattura'            => today(),
            'data_ricezione'          => today(),
            'imponibile'              => 500.00,
            'iva_totale'              => 110.00,
            'totale'                  => 610.00,
            'stato'                   => StatoFatturaAcquisto::Ricevuta,
            'user_id'                 => $this->admin->id,
        ]);

        $fattura->update(['stato' => StatoFatturaAcquisto::Registrata]);

        $count = RegistroIva::where('tipo_registro', 'acquisti')
            ->whereYear('data_registrazione', now()->year)
            ->whereMonth('data_registrazione', now()->month)
            ->count();

        $this->assertEquals(1, $count);
    }
}
