<?php

namespace Tests\Feature;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Actions\Scadenze\CreaScadenzeAutomaticheAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoScadenza;
use App\Jobs\InviaRichiamiScadenza;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\NotificaLog;
use App\Models\Scadenza;
use App\Models\Setting;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\EmailTemplateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Step6ScadenziarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private Veicolo $veicolo;
    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-06-12 10:00:00'));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Mario',
            'cognome' => 'Rossi',
            'email'   => 'mario.rossi@example.com',
        ]);

        $this->veicolo = Veicolo::create([
            'tipo'          => 'auto',
            'targa'         => 'AB123CD',
            'marca'         => 'Fiat',
            'modello'       => 'Panda',
            'alimentazione' => 'benzina',
            'km_attuali'    => 50000,
        ]);

        $numero = app(\App\Actions\Commessa\GeneraNumeroProgressivoAction::class)->execute();
        $this->commessa = Commessa::create([
            'numero'              => $numero,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'tagliando',
            'stato'               => StatoCommessa::Bozza,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test intervento',
            'user_id'             => $this->admin->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // --- EmailTemplateService ---

    public function test_compila_template_sostituisce_variabili(): void
    {
        Setting::set('template_email_accettazione', implode("\n", [
            'Oggetto: Veicolo accettato {{NUMERO_COMMESSA}}',
            '',
            'Gentile {{NOME_CLIENTE}}, il suo veicolo {{TARGA}} è stato accettato.',
        ]));

        $service = new EmailTemplateService();
        $risultato = $service->compila('template_email_accettazione', [
            'NUMERO_COMMESSA' => 'C-2026-001',
            'NOME_CLIENTE'    => 'Mario Rossi',
            'TARGA'           => 'AB123CD',
        ]);

        $this->assertEquals('Veicolo accettato C-2026-001', $risultato['oggetto']);
        $this->assertStringContainsString('Mario Rossi', $risultato['corpo']);
        $this->assertStringContainsString('AB123CD', $risultato['corpo']);
        $this->assertStringNotContainsString('{{', $risultato['corpo']);
    }

    public function test_compila_template_inesistente_restituisce_stringhe_vuote(): void
    {
        $service = new EmailTemplateService();
        $risultato = $service->compila('template_inesistente', []);

        $this->assertEquals('', $risultato['oggetto']);
        $this->assertEquals('', $risultato['corpo']);
    }

    // --- AggiornaStatoAction: accodamento email ---

    public function test_transizione_accettata_accoda_email_se_abilitata(): void
    {
        Queue::fake();
        Mail::fake();

        Setting::set('notifiche_email_abilitato', '1');
        Setting::set('template_email_accettazione', "Oggetto: Test accettazione\n\nCorpo email");

        app(AggiornaStatoAction::class)->execute(
            $this->commessa,
            StatoCommessa::Accettata,
            $this->admin,
        );

        Mail::assertQueued(\App\Mail\NotificaCommessa::class);

        $log = NotificaLog::where('commessa_id', $this->commessa->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->cliente->email, $log->destinatario);
    }

    public function test_transizione_non_accoda_email_se_disabilitata(): void
    {
        Mail::fake();
        Setting::set('notifiche_email_abilitato', '0');

        app(AggiornaStatoAction::class)->execute(
            $this->commessa,
            StatoCommessa::Accettata,
            $this->admin,
        );

        Mail::assertNothingQueued();
        $this->assertEquals(0, NotificaLog::count());
    }

    public function test_transizione_non_accoda_email_se_cliente_senza_email(): void
    {
        Mail::fake();
        Setting::set('notifiche_email_abilitato', '1');

        $this->cliente->update(['email' => null]);

        app(AggiornaStatoAction::class)->execute(
            $this->commessa,
            StatoCommessa::Accettata,
            $this->admin,
        );

        Mail::assertNothingQueued();
        $this->assertEquals(0, NotificaLog::count());
    }

    public function test_no_email_duplicata_per_stessa_commessa(): void
    {
        Mail::fake();
        Setting::set('notifiche_email_abilitato', '1');
        Setting::set('template_email_accettazione', "Oggetto: Test\n\nCorpo");

        // Pre-inserisce un log recente (simula notifica già accodata meno di 5 minuti fa)
        NotificaLog::create([
            'tipo'         => 'email',
            'destinatario' => $this->cliente->email,
            'oggetto'      => 'Test',
            'corpo'        => 'Corpo',
            'stato'        => \App\Enums\StatoNotifica::InCoda,
            'commessa_id'  => $this->commessa->id,
            'cliente_id'   => $this->cliente->id,
            'tentativi'    => 0,
        ]);

        // La transizione non deve creare un secondo log perché ne esiste già uno in_coda (<5 min)
        app(AggiornaStatoAction::class)->execute(
            $this->commessa,
            StatoCommessa::Accettata,
            $this->admin,
        );

        $this->assertEquals(1, NotificaLog::where('commessa_id', $this->commessa->id)->count());
    }

    // --- CreaScadenzeAutomaticheAction ---

    public function test_suggerisci_tagliando_da_riga_commessa(): void
    {
        CommessaRiga::create([
            'commessa_id'       => $this->commessa->id,
            'tipo'              => \App\Enums\TipoRiga::Manodopera,
            'descrizione'       => 'Tagliando completo cambio olio filtro olio',
            'quantita'          => 1,
            'prezzo_unitario'   => 100,
            'iva_percentuale'   => 22,
            'sconto_percentuale' => 0,
            'ordinamento'       => 1,
        ]);

        $action = new CreaScadenzeAutomaticheAction();
        $suggerimenti = $action->suggerisci($this->commessa);

        $this->assertCount(1, $suggerimenti);
        $this->assertEquals(TipoScadenza::Tagliando, $suggerimenti[0]['tipo']);
        $this->assertEquals(65000, $suggerimenti[0]['km_scadenza']); // 50000 + 15000
    }

    public function test_suggerisci_revisione_da_riga_commessa(): void
    {
        CommessaRiga::create([
            'commessa_id'       => $this->commessa->id,
            'tipo'              => \App\Enums\TipoRiga::Manodopera,
            'descrizione'       => 'Revisione periodica obbligatoria',
            'quantita'          => 1,
            'prezzo_unitario'   => 60,
            'iva_percentuale'   => 22,
            'sconto_percentuale' => 0,
            'ordinamento'       => 1,
        ]);

        $action = new CreaScadenzeAutomaticheAction();
        $suggerimenti = $action->suggerisci($this->commessa);

        $this->assertCount(1, $suggerimenti);
        $this->assertEquals(TipoScadenza::Revisione, $suggerimenti[0]['tipo']);
        $this->assertTrue($suggerimenti[0]['data_scadenza']->isAfter(now()->addMonths(20)));
    }

    public function test_salva_non_crea_duplicati(): void
    {
        // Scadenza tagliando già esistente nel futuro
        Scadenza::create([
            'veicolo_id'   => $this->veicolo->id,
            'cliente_id'   => $this->cliente->id,
            'tipo'         => TipoScadenza::Tagliando,
            'data_scadenza' => now()->addMonths(6),
        ]);

        $suggerimenti = [[
            'tipo'          => TipoScadenza::Tagliando,
            'descrizione'   => 'Prossimo tagliando',
            'data_scadenza' => now()->addYear(),
            'km_scadenza'   => 65000,
        ]];

        app(CreaScadenzeAutomaticheAction::class)->salva(
            $this->commessa,
            $suggerimenti,
            ['tagliando'],
        );

        // Non deve aver creato una seconda scadenza
        $this->assertEquals(1, Scadenza::where('veicolo_id', $this->veicolo->id)
            ->where('tipo', 'tagliando')->count());
    }

    // --- Job InviaRichiamiScadenza ---

    public function test_job_invia_richiamo_scadenza_imminente(): void
    {
        Mail::fake();

        Setting::set('notifiche_email_abilitato', '1');
        Setting::set('template_email_richiamo_scadenza', "Oggetto: Promemoria {{TIPO_SCADENZA}} — {{TARGA}}\n\nCorpo");

        Scadenza::create([
            'veicolo_id'            => $this->veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'tipo'                  => TipoScadenza::Revisione,
            'data_scadenza'         => now()->addDays(15),
            'notifica_giorni_prima' => 30,
            'notifica_disabilitata' => false,
        ]);

        app(InviaRichiamiScadenza::class)->handle(
            new \App\Services\EmailTemplateService(),
            new \App\Services\MailConfigService(),
        );

        Mail::assertSent(\App\Mail\NotificaRichiamo::class);

        $log = NotificaLog::first();
        $this->assertNotNull($log);
        $this->assertEquals('inviata', $log->stato->value);
        $this->assertNotNull($log->inviata_at);
    }

    public function test_job_non_invia_se_notifiche_disabilitate(): void
    {
        Mail::fake();
        Setting::set('notifiche_email_abilitato', '0');

        Scadenza::create([
            'veicolo_id'            => $this->veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'tipo'                  => TipoScadenza::Tagliando,
            'data_scadenza'         => now()->addDays(10),
            'notifica_giorni_prima' => 30,
        ]);

        app(InviaRichiamiScadenza::class)->handle(
            new \App\Services\EmailTemplateService(),
            new \App\Services\MailConfigService(),
        );

        Mail::assertNothingSent();
    }

    public function test_job_idempotente_non_reinvia_nelle_ultime_24h(): void
    {
        Mail::fake();

        Setting::set('notifiche_email_abilitato', '1');
        Setting::set('template_email_richiamo_scadenza', "Oggetto: Test\n\nCorpo");

        $scadenza = Scadenza::create([
            'veicolo_id'            => $this->veicolo->id,
            'cliente_id'            => $this->cliente->id,
            'tipo'                  => TipoScadenza::Assicurazione,
            'data_scadenza'         => now()->addDays(5),
            'notifica_giorni_prima' => 30,
        ]);

        // Simula un log recente (inviato 2 ore fa)
        NotificaLog::create([
            'tipo'        => 'email',
            'destinatario' => $this->cliente->email,
            'oggetto'     => 'Test',
            'corpo'       => 'Corpo',
            'stato'       => 'inviata',
            'scadenza_id' => $scadenza->id,
            'cliente_id'  => $this->cliente->id,
            'tentativi'   => 1,
            'inviata_at'  => now()->subHours(2),
            'created_at'  => now()->subHours(2),
            'updated_at'  => now()->subHours(2),
        ]);

        app(InviaRichiamiScadenza::class)->handle(
            new \App\Services\EmailTemplateService(),
            new \App\Services\MailConfigService(),
        );

        // Non deve aver inviato una seconda email
        Mail::assertNothingSent();
    }

    // --- Portale cliente ---

    public function test_portale_cliente_url_firmato_funzionante(): void
    {
        $url = URL::signedRoute('cliente.portale', [
            'token' => encrypt($this->cliente->id),
        ], now()->addDays(30));

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSeeText($this->cliente->nome_completo);
    }

    public function test_portale_cliente_url_non_firmato_rifiutato(): void
    {
        $response = $this->get(route('cliente.portale', [
            'token' => encrypt($this->cliente->id),
        ]));

        $response->assertStatus(403);
    }

    public function test_portale_cliente_non_mostra_dati_altri_clienti(): void
    {
        $altroCliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Altro',
            'cognome' => 'Cliente',
        ]);

        Commessa::create([
            'numero'              => 'C-2026-999',
            'cliente_id'          => $altroCliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'tagliando',
            'stato'               => StatoCommessa::Accettata,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test altro cliente',
            'user_id'             => $this->admin->id,
        ]);

        $url = URL::signedRoute('cliente.portale', [
            'token' => encrypt($this->cliente->id),
        ], now()->addDays(30));

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertDontSeeText('C-2026-999');
        $response->assertDontSeeText('Altro Cliente');
    }

    // --- Scadenziario route ---

    public function test_admin_accede_allo_scadenziario(): void
    {
        $response = $this->actingAs($this->admin)->get(route('scadenziario.index'));
        $response->assertStatus(200);
    }

    public function test_meccanico_non_accede_allo_scadenziario(): void
    {
        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $response = $this->actingAs($meccanico)->get(route('scadenziario.index'));
        $response->assertStatus(403);
    }

    // --- Impostazioni email route ---

    public function test_admin_accede_a_impostazioni_email(): void
    {
        $response = $this->actingAs($this->admin)->get(route('impostazioni.email'));
        $response->assertStatus(200);
    }
}
