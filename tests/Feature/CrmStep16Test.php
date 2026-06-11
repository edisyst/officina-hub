<?php

namespace Tests\Feature;

use App\Enums\SegmentoCrm;
use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Jobs\InviaAuguriCompleanno;
use App\Models\CampagnaEmail;
use App\Models\CampagnaInvio;
use App\Models\Cliente;
use App\Models\CrmNota;
use App\Models\User;
use App\Services\Crm\SegmentazioneService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmStep16Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::create(['name' => $r, 'guard_name' => 'web']);
        }
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // --- SegmentazioneService unit tests ---

    public function test_segmento_nuovo(): void
    {
        $service = app(SegmentazioneService::class);

        $cliente = new Cliente([
            'tipo'             => TipoCliente::Fisica,
            'nome'             => 'Mario',
            'cognome'          => 'Rossi',
            'ultima_visita_at' => null,
            'numero_visite'    => 0,
            'valore_lifetime'  => 0,
        ]);
        $cliente->created_at = now();

        $segmento = $service->calcolaSegmentoDaModello($cliente);
        $this->assertEquals(SegmentoCrm::Nuovo, $segmento);
    }

    public function test_segmento_perso(): void
    {
        $service = app(SegmentazioneService::class);

        $cliente = new Cliente([
            'tipo'             => TipoCliente::Fisica,
            'nome'             => 'Luigi',
            'cognome'          => 'Verdi',
            'ultima_visita_at' => now()->subMonths(30),
            'numero_visite'    => 5,
            'valore_lifetime'  => 100,
        ]);

        $segmento = $service->calcolaSegmentoDaModello($cliente);
        $this->assertEquals(SegmentoCrm::Perso, $segmento);
    }

    public function test_segmento_a_rischio(): void
    {
        $service = app(SegmentazioneService::class);

        $cliente = new Cliente([
            'tipo'             => TipoCliente::Fisica,
            'nome'             => 'Anna',
            'cognome'          => 'Neri',
            'ultima_visita_at' => now()->subMonths(18),
            'numero_visite'    => 3,
            'valore_lifetime'  => 100,
        ]);

        $segmento = $service->calcolaSegmentoDaModello($cliente);
        $this->assertEquals(SegmentoCrm::ARischio, $segmento);
    }

    public function test_segmento_attivo(): void
    {
        $service = app(SegmentazioneService::class);

        $cliente = new Cliente([
            'tipo'             => TipoCliente::Fisica,
            'nome'             => 'Carlo',
            'cognome'          => 'Blu',
            'ultima_visita_at' => now()->subMonths(6),
            'numero_visite'    => 4,
            'valore_lifetime'  => 100,
        ]);

        $segmento = $service->calcolaSegmentoDaModello($cliente);
        $this->assertEquals(SegmentoCrm::Attivo, $segmento);
    }

    // --- Campagna email: solo consensati ---

    public function test_campagna_inviata_solo_a_consensati(): void
    {
        Mail::fake();

        // Un cliente con consenso, uno senza
        $conConsenso = Cliente::create([
            'tipo'               => TipoCliente::Fisica,
            'nome'               => 'Con',
            'cognome'            => 'Consenso',
            'email'              => 'con@example.com',
            'consenso_marketing' => true,
            'segmento_crm'       => SegmentoCrm::Attivo->value,
        ]);

        $senzaConsenso = Cliente::create([
            'tipo'               => TipoCliente::Fisica,
            'nome'               => 'Senza',
            'cognome'            => 'Consenso',
            'email'              => 'senza@example.com',
            'consenso_marketing' => false,
            'segmento_crm'       => SegmentoCrm::Attivo->value,
        ]);

        $campagna = CampagnaEmail::create([
            'nome'            => 'Test campagna',
            'oggetto'         => 'Oggetto test',
            'corpo'           => 'Corpo test {{NOME_CLIENTE}}',
            'stato'           => 'pianificata',
            'segmento_target' => 'attivo',
            'user_id'         => $this->admin->id,
        ]);

        $job = new \App\Jobs\InviaCampagnaEmail($campagna->id);
        $job->handle(
            app(\App\Services\Crm\SegmentazioneService::class),
            app(\App\Services\EmailTemplateService::class),
            app(\App\Services\MailConfigService::class),
        );

        // Solo il cliente con consenso ha l'invio registrato come 'inviata'
        $this->assertDatabaseHas('campagna_invii', [
            'campagna_email_id' => $campagna->id,
            'cliente_id'        => $conConsenso->id,
            'stato'             => 'inviata',
        ]);

        $this->assertDatabaseMissing('campagna_invii', [
            'campagna_email_id' => $campagna->id,
            'cliente_id'        => $senzaConsenso->id,
        ]);
    }

    // --- Job compleanno: solo clienti con compleanno oggi ---

    public function test_auguri_compleanno_solo_oggi(): void
    {
        Mail::fake();

        $oggi = now();

        // Cliente con compleanno oggi, consenso e email
        $clienteOggi = Cliente::create([
            'tipo'               => TipoCliente::Fisica,
            'nome'               => 'Buon',
            'cognome'            => 'Compleanno',
            'email'              => 'compleanno@example.com',
            'consenso_marketing' => true,
            'data_nascita'       => Carbon::create(1990, $oggi->month, $oggi->day),
        ]);

        // Cliente con compleanno domani
        $clienteDomani = Cliente::create([
            'tipo'               => TipoCliente::Fisica,
            'nome'               => 'Non',
            'cognome'            => 'Oggi',
            'email'              => 'domani@example.com',
            'consenso_marketing' => true,
            'data_nascita'       => Carbon::create(1990, $oggi->copy()->addDay()->month, $oggi->copy()->addDay()->day),
        ]);

        // Simula setting abilitato
        \App\Models\Setting::updateOrCreate(['key' => 'notifiche_email_abilitato'], ['value' => '1']);
        \App\Models\Setting::updateOrCreate(['key' => 'template_email_compleanno'], ['value' => "Oggetto: Auguri {{NOME_CLIENTE}}!\n\nBuon compleanno {{NOME_CLIENTE}}."]);
        \App\Models\Setting::updateOrCreate(['key' => 'sconto_compleanno_percentuale'], ['value' => '10']);

        $job = new InviaAuguriCompleanno();
        $job->handle(
            app(\App\Services\EmailTemplateService::class),
            app(\App\Services\MailConfigService::class),
        );

        // Solo clienteOggi deve avere il log
        $this->assertDatabaseHas('notifiche_log', [
            'cliente_id' => $clienteOggi->id,
            'sottotipo'  => 'compleanno',
        ]);

        $this->assertDatabaseMissing('notifiche_log', [
            'cliente_id' => $clienteDomani->id,
            'sottotipo'  => 'compleanno',
        ]);
    }

    // --- Filtro lista clienti per segmento ---

    public function test_filtro_clienti_per_segmento(): void
    {
        Cliente::create([
            'tipo'        => TipoCliente::Fisica,
            'nome'        => 'A',
            'cognome'     => 'Attivo',
            'segmento_crm' => SegmentoCrm::Attivo->value,
        ]);
        Cliente::create([
            'tipo'        => TipoCliente::Fisica,
            'nome'        => 'P',
            'cognome'     => 'Perso',
            'segmento_crm' => SegmentoCrm::Perso->value,
        ]);

        $attivi = Cliente::where('segmento_crm', SegmentoCrm::Attivo->value)->count();
        $persi  = Cliente::where('segmento_crm', SegmentoCrm::Perso->value)->count();

        $this->assertEquals(1, $attivi);
        $this->assertEquals(1, $persi);
    }

    // --- Nota CRM ---

    public function test_nota_crm_aggiunta(): void
    {
        $cliente = Cliente::create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Test',
            'cognome' => 'Nota',
        ]);

        CrmNota::create([
            'cliente_id'       => $cliente->id,
            'user_id'          => $this->admin->id,
            'testo'            => 'Chiamato per preventivo gomme',
            'tipo'             => 'chiamata',
            'data_interazione' => now(),
        ]);

        $this->assertDatabaseHas('crm_note', [
            'cliente_id' => $cliente->id,
            'tipo'       => 'chiamata',
        ]);

        $this->assertEquals(1, $cliente->crmNote()->count());
    }

    // --- Route access ---

    public function test_crm_dashboard_accessibile_admin(): void
    {
        $resp = $this->actingAs($this->admin)->get('/crm/dashboard');
        $resp->assertStatus(200);
    }

    public function test_crm_campagne_accessibile_admin(): void
    {
        $resp = $this->actingAs($this->admin)->get('/crm/campagne');
        $resp->assertStatus(200);
    }

    public function test_crm_dashboard_non_accessibile_accettatore(): void
    {
        $accettatore = User::factory()->create();
        $accettatore->assignRole('accettatore');

        $resp = $this->actingAs($accettatore)->get('/crm/dashboard');
        $resp->assertStatus(403);
    }
}
