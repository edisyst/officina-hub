<?php

namespace Tests\Feature;

use App\Actions\Fatturazione\GeneraFatturaGaranziaAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Enums\TipoEmissione;
use App\Enums\TipoGaranzia;
use App\Models\CasaMadre;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Garanzia;
use App\Enums\TipoCliente;
use App\Enums\TipoVeicolo;
use App\Enums\Alimentazione;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GaranzieStep17Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Veicolo $veicolo;
    protected Commessa $commessa;
    protected CasaMadre $casaMadre;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->seed(RuoliSeeder::class);

        $this->admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@test.it',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('admin');

        $cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
            'email'   => 'mario@test.it',
        ]);

        $this->veicolo = Veicolo::create([
            'cliente_id'  => $cliente->id,
            'tipo'        => TipoVeicolo::Auto,
            'marca'       => 'Fiat',
            'modello'     => 'Punto',
            'alimentazione' => Alimentazione::Benzina,
        ]);

        $this->commessa = Commessa::create([
            'numero'              => 'C-2024-0001',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => TipoCommessa::Meccanica,
            'stato'               => StatoCommessa::InLavorazione,
            'data_ingresso'       => now(),
            'descrizione_cliente' => '',
            'user_id'             => $this->admin->id,
        ]);

        $this->casaMadre = CasaMadre::create([
            'ragione_sociale' => 'FCA Italy S.p.A.',
            'partita_iva'     => '00536750164',
        ]);
    }

    /** Garanzia attiva su veicolo: creazione e scope attive */
    public function test_garanzia_attiva_su_veicolo(): void
    {
        $garanzia = Garanzia::create([
            'veicolo_id'  => $this->veicolo->id,
            'tipo'        => TipoGaranzia::GaranziaCostruttore->value,
            'descrizione' => 'Garanzia 5 anni',
            'data_inizio' => now()->subYear()->toDateString(),
            'data_fine'   => now()->addYears(4)->toDateString(),
            'attiva'      => true,
        ]);

        $this->assertTrue($garanzia->attiva);
        $this->assertFalse($garanzia->isScaduta());
        $this->assertFalse($garanzia->isInScadenza());

        $this->assertEquals(1, $this->veicolo->garanzie()->attive()->count());
    }

    /** Garanzia in scadenza (< 30 gg) */
    public function test_garanzia_in_scadenza(): void
    {
        $garanzia = Garanzia::create([
            'veicolo_id'  => $this->veicolo->id,
            'tipo'        => TipoGaranzia::GaranziaCostruttore->value,
            'descrizione' => 'In scadenza',
            'data_inizio' => now()->subYears(5)->toDateString(),
            'data_fine'   => now()->addDays(15)->toDateString(),
            'attiva'      => true,
        ]);

        $this->assertFalse($garanzia->isScaduta());
        $this->assertTrue($garanzia->isInScadenza());
        $this->assertEquals('badge-warning', $garanzia->badgeClass());
    }

    /** Garanzia scaduta */
    public function test_garanzia_scaduta(): void
    {
        $garanzia = Garanzia::create([
            'veicolo_id'  => $this->veicolo->id,
            'tipo'        => TipoGaranzia::GaranziaUsato->value,
            'descrizione' => 'Scaduta',
            'data_inizio' => now()->subYears(3)->toDateString(),
            'data_fine'   => now()->subMonth()->toDateString(),
            'attiva'      => true,
        ]);

        $this->assertTrue($garanzia->isScaduta());
        $this->assertEquals('badge-secondary', $garanzia->badgeClass());
        $this->assertEquals(0, $this->veicolo->garanzie()->attive()->count());
    }

    /** Riga in garanzia: totale cliente = 0, totale casa madre = prezzo pieno */
    public function test_riga_in_garanzia_totale_cliente_zero(): void
    {
        $riga = CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Sostituzione cinghia distribuzione',
            'quantita'           => 2,
            'prezzo_unitario'    => 100,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => true,
            'casa_madre_id'      => $this->casaMadre->id,
        ]);

        $this->assertEquals(0.0, $riga->totale_cliente);
        $this->assertGreaterThan(0, $riga->totale_casa_madre);
        $this->assertEquals($riga->totale, $riga->totale_casa_madre);
    }

    /** Riga normale: totale cliente = pieno, totale casa madre = 0 */
    public function test_riga_normale_totale_cliente_pieno(): void
    {
        $riga = CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Cambio olio',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => false,
        ]);

        $this->assertGreaterThan(0, $riga->totale_cliente);
        $this->assertEquals(0.0, $riga->totale_casa_madre);
        $this->assertEquals($riga->totale, $riga->totale_cliente);
    }

    /** CommessaRigaObserver aggiorna ha_righe_garanzia */
    public function test_observer_aggiorna_ha_righe_garanzia(): void
    {
        $this->assertFalse((bool) $this->commessa->fresh()->ha_righe_garanzia);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Test',
            'quantita'           => 1,
            'prezzo_unitario'    => 100,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => true,
        ]);

        $this->assertTrue((bool) $this->commessa->fresh()->ha_righe_garanzia);
    }

    /** Totale commessa cliente + casa madre = totale lordo */
    public function test_totali_commessa_sommano_al_totale_lordo(): void
    {
        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Cambio olio',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => false,
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Sostituzione freni',
            'quantita'           => 1,
            'prezzo_unitario'    => 200,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => true,
            'casa_madre_id'      => $this->casaMadre->id,
        ]);

        $this->commessa->load('righe');

        $totaleCliente    = $this->commessa->totale_cliente;
        $totaleCasaMadre  = $this->commessa->totale_casa_madre;
        $totaleLordo      = $this->commessa->totale_lordo;

        $this->assertEquals(round($totaleLordo, 2), round($totaleCliente + $totaleCasaMadre, 2));
        $this->assertGreaterThan(0, $totaleCliente);
        $this->assertGreaterThan(0, $totaleCasaMadre);
    }

    /** GeneraFatturaGaranziaAction: fattura cliente + fattura casa madre */
    public function test_genera_fattura_garanzia_doppia(): void
    {
        $garanzia = Garanzia::create([
            'veicolo_id'      => $this->veicolo->id,
            'tipo'            => TipoGaranzia::GaranziaCostruttore->value,
            'descrizione'     => 'Garanzia costruttore',
            'data_inizio'     => now()->subYear()->toDateString(),
            'data_fine'       => now()->addYears(4)->toDateString(),
            'attiva'          => true,
            'numero_pratica'  => 'WRN-2024-001',
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Cambio olio',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => false,
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Sostituzione freni in garanzia',
            'quantita'           => 1,
            'prezzo_unitario'    => 200,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => true,
            'garanzia_id'        => $garanzia->id,
            'casa_madre_id'      => $this->casaMadre->id,
        ]);

        $this->commessa->update(['ha_righe_garanzia' => true]);

        $documenti = app(GeneraFatturaGaranziaAction::class)->execute($this->commessa);

        $this->assertCount(2, $documenti); // 1 cliente + 1 casa madre

        $docCliente   = $documenti[0];
        $docCasaMadre = $documenti[1];

        $this->assertEquals(TipoEmissione::Cliente, $docCliente->tipo_emissione);
        $this->assertEquals(TipoEmissione::CasaMadre, $docCasaMadre->tipo_emissione);
        $this->assertEquals($this->casaMadre->id, $docCasaMadre->casa_madre_id);

        // Importi: cliente = 50 + IVA22%, casa madre = 200 + IVA22%
        $this->assertEquals(round(50 * 1.22, 2), round((float) $docCliente->totale, 2));
        $this->assertEquals(round(200 * 1.22, 2), round((float) $docCasaMadre->totale, 2));

        // Somma = totale commessa
        $this->commessa->load('righe');
        $this->assertEquals(
            round((float) $this->commessa->totale_lordo, 2),
            round((float) $docCliente->totale + (float) $docCasaMadre->totale, 2)
        );
    }

    /** Nessuna riga in garanzia: exception */
    public function test_genera_fattura_garanzia_senza_righe_garanzia(): void
    {
        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Cambio olio',
            'quantita'           => 1,
            'prezzo_unitario'    => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => false,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(GeneraFatturaGaranziaAction::class)->execute($this->commessa);
    }

    /** Route access: report garanzie — admin OK, meccanico denied */
    public function test_route_report_garanzie_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('garanzie.report'))
            ->assertOk();
    }

    public function test_route_report_garanzie_meccanico_denied(): void
    {
        $meccanico = User::create([
            'name'     => 'Meccanico',
            'email'    => 'meccanico@test.it',
            'password' => bcrypt('password'),
        ]);
        $meccanico->assignRole('meccanico');

        $this->actingAs($meccanico)
            ->get(route('garanzie.report'))
            ->assertForbidden();
    }

    /** Route access: impostazioni case madri — admin OK */
    public function test_route_case_madri_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('impostazioni.case-madri'))
            ->assertOk();
    }

    /** Righe con case madri diverse: una fattura per casa madre */
    public function test_genera_fattura_garanzia_multi_casa_madre(): void
    {
        $casaMadre2 = CasaMadre::create([
            'ragione_sociale' => 'BMW Group Italia S.p.A.',
            'partita_iva'     => '01598440154',
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Garanzia FCA',
            'quantita'           => 1,
            'prezzo_unitario'    => 100,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => true,
            'casa_madre_id'      => $this->casaMadre->id,
        ]);

        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Garanzia BMW',
            'quantita'           => 1,
            'prezzo_unitario'    => 150,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 1,
            'in_garanzia'        => true,
            'casa_madre_id'      => $casaMadre2->id,
        ]);

        $documenti = app(GeneraFatturaGaranziaAction::class)->execute($this->commessa);

        // 1 fattura cliente (vuota) + 2 fatture case madri
        $this->assertCount(3, $documenti);

        $tipi = collect($documenti)->pluck('tipo_emissione')->map(fn($t) => $t->value)->toArray();
        $this->assertContains('cliente', $tipi);
        $this->assertEquals(2, collect($tipi)->filter(fn($t) => $t === 'casa_madre')->count());
    }

    /** CasaMadre nel XML FatturaPA: CessionarioCommittente usa dati casa madre */
    public function test_xml_fattura_casa_madre_usa_dati_casa_madre(): void
    {
        CommessaRiga::create([
            'commessa_id'        => $this->commessa->id,
            'tipo'               => \App\Enums\TipoRiga::Manodopera->value,
            'descrizione'        => 'Sostituzione freni',
            'quantita'           => 1,
            'prezzo_unitario'    => 200,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ordinamento'        => 0,
            'in_garanzia'        => true,
            'casa_madre_id'      => $this->casaMadre->id,
        ]);

        $documenti = app(GeneraFatturaGaranziaAction::class)->execute($this->commessa);
        $docCasaMadre = collect($documenti)->firstWhere('tipo_emissione', TipoEmissione::CasaMadre);

        $this->assertNotNull($docCasaMadre);
        $this->assertEquals($this->casaMadre->id, $docCasaMadre->casa_madre_id);

        // Carica la relazione per verifica
        $docCasaMadre->load('casaMadre');
        $this->assertEquals('FCA Italy S.p.A.', $docCasaMadre->casaMadre->ragione_sociale);
    }
}
