<?php

namespace Tests\Feature;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Actions\Commessa\GeneraNumeroProgressivoAction;
use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Setting;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommessaFlussoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $meccanico;
    private User $accettatore;
    private User $cassa;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->meccanico = User::factory()->create();
        $this->meccanico->assignRole('meccanico');

        $this->accettatore = User::factory()->create();
        $this->accettatore->assignRole('accettatore');

        $this->cassa = User::factory()->create();
        $this->cassa->assignRole('cassa');

        $this->cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Cliente']);
        $this->veicolo = Veicolo::create([
            'tipo' => 'auto',
            'targa' => 'TE001ST',
            'marca' => 'Test',
            'modello' => 'Auto',
            'alimentazione' => 'benzina',
        ]);
    }

    private function creaCommessa(): Commessa
    {
        $numero = app(GeneraNumeroProgressivoAction::class)->execute();
        return Commessa::create([
            'numero' => $numero,
            'cliente_id' => $this->cliente->id,
            'veicolo_id' => $this->veicolo->id,
            'tipo' => 'meccanica',
            'stato' => 'bozza',
            'data_ingresso' => now(),
            'descrizione_cliente' => 'Test problema',
            'user_id' => $this->accettatore->id,
        ]);
    }

    public function test_genera_numero_progressivo(): void
    {
        $numero = app(GeneraNumeroProgressivoAction::class)->execute();
        $anno = date('Y');
        $this->assertEquals("COM-{$anno}-0001", $numero);

        // Il secondo numero deve essere 0002
        Commessa::create([
            'numero' => $numero,
            'cliente_id' => $this->cliente->id,
            'veicolo_id' => $this->veicolo->id,
            'tipo' => 'meccanica',
            'stato' => 'bozza',
            'data_ingresso' => now(),
            'descrizione_cliente' => 'Test',
            'user_id' => $this->admin->id,
        ]);

        $numero2 = app(GeneraNumeroProgressivoAction::class)->execute();
        $this->assertEquals("COM-{$anno}-0002", $numero2);
    }

    public function test_flusso_completo_stati(): void
    {
        $commessa = $this->creaCommessa();
        $azione = app(AggiornaStatoAction::class);

        $this->assertEquals(StatoCommessa::Bozza, $commessa->stato);

        // bozza → accettata
        $azione->execute($commessa, StatoCommessa::Accettata, $this->accettatore);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Accettata, $commessa->stato);

        // accettata → in_lavorazione
        $azione->execute($commessa, StatoCommessa::InLavorazione, $this->meccanico);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::InLavorazione, $commessa->stato);

        // in_lavorazione → sospesa
        $azione->execute($commessa, StatoCommessa::Sospesa, $this->meccanico, 'Attesa ricambio');
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Sospesa, $commessa->stato);

        // sospesa → in_lavorazione
        $azione->execute($commessa, StatoCommessa::InLavorazione, $this->meccanico);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::InLavorazione, $commessa->stato);

        // in_lavorazione → completata
        $azione->execute($commessa, StatoCommessa::Completata, $this->meccanico);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Completata, $commessa->stato);

        // completata → consegnata
        $azione->execute($commessa, StatoCommessa::Consegnata, $this->accettatore);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Consegnata, $commessa->stato);
        $this->assertNotNull($commessa->data_consegna);

        // consegnata → fatturata
        $azione->execute($commessa, StatoCommessa::Fatturata, $this->cassa);
        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Fatturata, $commessa->stato);
    }

    public function test_transizione_non_ammessa_lancia_eccezione(): void
    {
        $commessa = $this->creaCommessa();
        $azione = app(AggiornaStatoAction::class);

        $this->expectException(ValidationException::class);
        // Impossibile passare da bozza a completata direttamente
        $azione->execute($commessa, StatoCommessa::Completata, $this->admin);
    }

    public function test_log_transizioni_registrato(): void
    {
        $commessa = $this->creaCommessa();
        $azione = app(AggiornaStatoAction::class);

        $azione->execute($commessa, StatoCommessa::Accettata, $this->accettatore, 'Prima nota');
        $azione->execute($commessa, StatoCommessa::InLavorazione, $this->meccanico);

        // Ordina per id per garantire ordine stabile anche in test veloci
        $log = $commessa->log()->orderBy('id')->get();
        $this->assertCount(2, $log);

        $primaTransizione = $log->first();
        $this->assertEquals('bozza', $primaTransizione->stato_da->value);
        $this->assertEquals('accettata', $primaTransizione->stato_a->value);
        $this->assertEquals('Prima nota', $primaTransizione->nota);
    }

    public function test_admin_puo_accedere_lista_commesse(): void
    {
        $this->actingAs($this->admin)->get(route('commesse.index'))->assertOk();
    }

    public function test_totali_righe_calcolati_correttamente(): void
    {
        $commessa = $this->creaCommessa();

        $commessa->righe()->create([
            'tipo' => 'manodopera',
            'descrizione' => '2 ore di lavoro',
            'quantita' => 2,
            'prezzo_unitario' => 50,
            'sconto_percentuale' => 0,
            'iva_percentuale' => 22,
        ]);

        $commessa->righe()->create([
            'tipo' => 'articolo',
            'descrizione' => 'Filtro olio',
            'quantita' => 1,
            'prezzo_unitario' => 15,
            'sconto_percentuale' => 10,
            'iva_percentuale' => 22,
        ]);

        $commessa->load('righe');

        // 2 × 50 = 100 imponibile prima riga
        // 1 × 15 × (1 - 10%) = 13.50 seconda riga
        $this->assertEqualsWithDelta(113.50, $commessa->totale_imponibile, 0.01);
        $this->assertEqualsWithDelta(113.50 * 0.22, $commessa->totale_iva, 0.01);
    }

    public function test_impostazioni_predefinite(): void
    {
        Setting::create(['key' => 'iva_default', 'value' => '22']);
        $this->assertEquals('22', Setting::get('iva_default'));
        $this->assertEquals('default', Setting::get('chiave_inesistente', 'default'));
    }
}
