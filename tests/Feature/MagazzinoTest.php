<?php

namespace Tests\Feature;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Actions\Commessa\GeneraNumeroProgressivoAction;
use App\Actions\Magazzino\CaricoManualeAction;
use App\Actions\Magazzino\RettificaInventarioAction;
use App\Actions\Magazzino\ScaricoCommessaAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\CategoriaArticolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MagazzinoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $accettatore;
    private User $meccanico;
    private Articolo $articolo;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->accettatore = User::factory()->create();
        $this->accettatore->assignRole('accettatore');

        $this->meccanico = User::factory()->create();
        $this->meccanico->assignRole('meccanico');

        $this->articolo = Articolo::create([
            'codice'          => 'TEST-001',
            'descrizione'     => 'Filtro olio test',
            'unita_misura'    => 'pz',
            'prezzo_acquisto' => 5.00,
            'prezzo_vendita'  => 12.00,
            'iva_percentuale' => 22,
            'scorta_minima'   => 5,
            'giacenza_attuale' => 0,
        ]);
    }

    private function creaCommessaConRigaArticolo(): Commessa
    {
        $cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Cliente']);
        $veicolo = Veicolo::create([
            'tipo' => 'auto', 'targa' => 'TE123ST', 'marca' => 'Test',
            'modello' => 'Auto', 'alimentazione' => 'benzina',
        ]);
        $numero = app(GeneraNumeroProgressivoAction::class)->execute();
        $commessa = Commessa::create([
            'numero' => $numero, 'cliente_id' => $cliente->id, 'veicolo_id' => $veicolo->id,
            'tipo' => 'meccanica', 'stato' => 'bozza', 'data_ingresso' => now(),
            'descrizione_cliente' => 'Test', 'user_id' => $this->admin->id,
        ]);

        $commessa->righe()->create([
            'tipo'            => 'articolo',
            'articolo_id'     => $this->articolo->id,
            'descrizione'     => 'Filtro olio test',
            'quantita'        => 2,
            'prezzo_unitario' => 12.00,
            'sconto_percentuale' => 0,
            'iva_percentuale' => 22,
        ]);

        return $commessa;
    }

    public function test_carico_manuale_aggiorna_giacenza(): void
    {
        $action = app(CaricoManualeAction::class);

        $movimento = $action->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 10,
            utente: $this->admin,
            prezzoUnitario: 5.00,
            documentoFornitore: 'DDT-2026-001',
        );

        $this->articolo->refresh();

        $this->assertEquals(10, $this->articolo->giacenza_attuale);
        $this->assertEquals(TipoMovimento::Carico, $movimento->tipo);
        $this->assertEquals(0, $movimento->giacenza_precedente);
        $this->assertEquals(10, $movimento->giacenza_successiva);
        $this->assertEquals(10, $movimento->quantita);
        $this->assertEquals('DDT-2026-001', $movimento->documento_fornitore);
    }

    public function test_rettifica_inventario_porta_giacenza_al_valore_atteso(): void
    {
        // Carico iniziale
        app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 10,
            utente: $this->admin,
        );

        $this->articolo->refresh();
        $this->assertEquals(10, $this->articolo->giacenza_attuale);

        // Rettifica a 7
        $movimento = app(RettificaInventarioAction::class)->execute(
            articolo: $this->articolo,
            nuovaGiacenza: 7,
            utente: $this->admin,
            nota: 'Conteggio fisico del 27/05/2026',
        );

        $this->articolo->refresh();

        $this->assertEquals(7, $this->articolo->giacenza_attuale);
        $this->assertEquals(TipoMovimento::Rettifica, $movimento->tipo);
        $this->assertEquals(3, $movimento->quantita); // differenza assoluta
        $this->assertEquals(10, $movimento->giacenza_precedente);
        $this->assertEquals(7, $movimento->giacenza_successiva);
    }

    public function test_scarico_automatico_da_commessa_al_completamento(): void
    {
        // Carico 20 pezzi
        app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 20,
            utente: $this->admin,
        );

        $commessa = $this->creaCommessaConRigaArticolo();

        $azione = app(AggiornaStatoAction::class);
        $azione->execute($commessa, StatoCommessa::Accettata, $this->accettatore);
        $azione->execute($commessa, StatoCommessa::InLavorazione, $this->meccanico);

        // Qui scatta l'observer al passaggio a "completata"
        $this->actingAs($this->meccanico);
        $azione->execute($commessa, StatoCommessa::Completata, $this->meccanico);

        $this->articolo->refresh();

        // 20 caricati - 2 scaricati dalla riga = 18
        $this->assertEquals(18, $this->articolo->giacenza_attuale);

        $scarico = MovimentoMagazzino::where('articolo_id', $this->articolo->id)
            ->where('tipo', TipoMovimento::Scarico->value)
            ->where('commessa_id', $commessa->id)
            ->first();

        $this->assertNotNull($scarico);
        $this->assertEquals(2, $scarico->quantita);
        $this->assertEquals(20, $scarico->giacenza_precedente);
        $this->assertEquals(18, $scarico->giacenza_successiva);
    }

    public function test_scarico_in_negativo_non_blocca_completamento_ma_nota_avviso(): void
    {
        // Nessun carico: giacenza = 0
        $this->assertEquals(0, $this->articolo->giacenza_attuale);

        $commessa = $this->creaCommessaConRigaArticolo();

        $azione = app(AggiornaStatoAction::class);
        $azione->execute($commessa, StatoCommessa::Accettata, $this->accettatore);
        $azione->execute($commessa, StatoCommessa::InLavorazione, $this->meccanico);

        $this->actingAs($this->meccanico);

        // Non deve lanciare eccezione
        $azione->execute($commessa, StatoCommessa::Completata, $this->meccanico);

        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Completata, $commessa->stato);

        $this->articolo->refresh();
        $this->assertEquals(-2, $this->articolo->giacenza_attuale);

        $scarico = MovimentoMagazzino::where('articolo_id', $this->articolo->id)
            ->where('tipo', TipoMovimento::Scarico->value)
            ->first();

        $this->assertNotNull($scarico);
        $this->assertStringContainsString('NEGATIVO', $scarico->note);
    }

    public function test_reso_fornitore_aumenta_giacenza(): void
    {
        $action = app(CaricoManualeAction::class);

        $action->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::ResoFornitore,
            quantita: 3,
            utente: $this->admin,
            note: 'Reso merce difettosa',
        );

        $this->articolo->refresh();
        $this->assertEquals(3, $this->articolo->giacenza_attuale);
    }

    public function test_movimenti_sono_immutabili_no_updated_at(): void
    {
        $movimento = app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 5,
            utente: $this->admin,
        );

        $this->assertNotNull($movimento->created_at);
        // Il modello ha timestamps = false, quindi non esiste updated_at sul record
        $this->assertFalse($movimento->timestamps);
    }

    public function test_scope_sotto_scorta_funziona(): void
    {
        // giacenza_attuale = 0, scorta_minima = 5 → sotto scorta
        $sottoScorta = Articolo::sottoScorta()->get();
        $this->assertCount(1, $sottoScorta);

        // Carico fino a scorta
        app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 10,
            utente: $this->admin,
        );

        $this->articolo->refresh();
        $sottoScorta = Articolo::sottoScorta()->get();
        $this->assertCount(0, $sottoScorta);
    }

    public function test_pagina_articoli_accessibile_ad_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('magazzino.articoli'))
            ->assertOk();
    }

    public function test_pagina_articoli_non_accessibile_a_meccanico(): void
    {
        $this->actingAs($this->meccanico)
            ->get(route('magazzino.articoli'))
            ->assertForbidden();
    }

    public function test_pagina_fornitori_accessibile_ad_accettatore(): void
    {
        $this->actingAs($this->accettatore)
            ->get(route('magazzino.fornitori'))
            ->assertOk();
    }
}
