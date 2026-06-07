<?php

namespace Tests\Feature;

use App\Models\ChecklistCompilata;
use App\Models\ChecklistRisposta;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistVoce;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use App\Enums\StatoCommessa;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\TestCase;

class PwaQrChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $meccanico;
    protected Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->meccanico = User::factory()->create();
        $this->meccanico->assignRole('meccanico');

        $cliente = Cliente::create([
            'tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Utente',
            'email' => 'test@test.it',
        ]);

        $veicolo = Veicolo::create(['marca' => 'Fiat', 'modello' => 'Panda', 'tipo' => 'auto']);

        $this->commessa = Commessa::create([
            'numero'              => 'COM-2026-0001',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'tipo'                => 'tagliando',
            'stato'               => StatoCommessa::InLavorazione,
            'user_id'             => $this->admin->id,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test',
        ]);
    }

    /** Il manifest.json esiste ed ha il contenuto corretto */
    public function test_manifest_json_accessibile(): void
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertEquals('Officina Hub', $data['name']);
        $this->assertEquals('/officina/marcatempo', $data['start_url']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertNotEmpty($data['icons']);
    }

    /** Il service worker esiste ed include le strategie di cache attese */
    public function test_service_worker_accessibile(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('STATIC_CACHE', $content);
        $this->assertStringContainsString('staleWhileRevalidate', $content);
    }

    /** Il QR code di una commessa restituisce SVG ben formato */
    public function test_qr_code_commessa_genera_svg(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('commesse.qr-code', $this->commessa->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');

        $svg = $response->getContent();
        $this->assertStringContainsString('<svg', $svg);

        // L'SVG deve essere ben formato (parsabile da DOM)
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($svg) !== false, 'Il QR code deve essere un SVG valido');
    }

    /** Un meccanico non può accedere al QR code */
    public function test_meccanico_non_puo_accedere_qr(): void
    {
        $this->actingAs($this->meccanico);

        $response = $this->get(route('commesse.qr-code', $this->commessa->id));
        $response->assertStatus(403);
    }

    /** Si può creare un template checklist con le sue voci */
    public function test_crea_template_con_voci(): void
    {
        $template = ChecklistTemplate::create([
            'nome'     => 'Tagliando standard',
            'attivo'   => true,
            'ordinamento' => 1,
        ]);

        ChecklistVoce::create([
            'checklist_template_id' => $template->id,
            'etichetta' => 'Livello olio motore OK',
            'tipo'      => 'si_no',
            'obbligatoria' => true,
            'ordinamento' => 1,
        ]);

        ChecklistVoce::create([
            'checklist_template_id' => $template->id,
            'etichetta' => 'Km percorsi',
            'tipo'      => 'numerico',
            'unita_misura' => 'km',
            'ordinamento' => 2,
        ]);

        $this->assertEquals(2, $template->voci()->count());
        $this->assertEquals('si_no', $template->voci->first()->tipo);
    }

    /** Una checklist compilata tiene traccia delle risposte per voce */
    public function test_compila_checklist_salva_risposte(): void
    {
        $template = ChecklistTemplate::create(['nome' => 'Test', 'attivo' => true, 'ordinamento' => 1]);
        $voce = ChecklistVoce::create([
            'checklist_template_id' => $template->id,
            'etichetta' => 'Test si/no',
            'tipo'      => 'si_no',
            'ordinamento' => 1,
        ]);

        $compilata = ChecklistCompilata::create([
            'checklist_template_id' => $template->id,
            'commessa_id'           => $this->commessa->id,
            'user_id'               => $this->meccanico->id,
        ]);

        ChecklistRisposta::create([
            'checklist_compilata_id' => $compilata->id,
            'checklist_voce_id'      => $voce->id,
            'valore_booleano'        => true,
        ]);

        $compilata->refresh();
        $this->assertEquals(1, $compilata->risposte()->count());
        $this->assertTrue($compilata->risposte->first()->valore_booleano);
        $this->assertFalse($compilata->isCompletata());
    }

    /** Finalizzare una checklist imposta completata_at */
    public function test_completata_at_impostato_su_finalizzazione(): void
    {
        $template = ChecklistTemplate::create(['nome' => 'Test', 'attivo' => true, 'ordinamento' => 1]);

        $compilata = ChecklistCompilata::create([
            'checklist_template_id' => $template->id,
            'commessa_id'           => $this->commessa->id,
            'user_id'               => $this->meccanico->id,
        ]);

        $this->assertFalse($compilata->isCompletata());

        $compilata->update(['completata_at' => now()]);
        $compilata->refresh();

        $this->assertTrue($compilata->isCompletata());
    }

    /** Il percentuale completamento è calcolato correttamente */
    public function test_percentuale_completamento(): void
    {
        $template = ChecklistTemplate::create(['nome' => 'Perc', 'attivo' => true, 'ordinamento' => 1]);
        $v1 = ChecklistVoce::create(['checklist_template_id' => $template->id, 'etichetta' => 'V1', 'tipo' => 'si_no', 'ordinamento' => 1]);
        $v2 = ChecklistVoce::create(['checklist_template_id' => $template->id, 'etichetta' => 'V2', 'tipo' => 'si_no', 'ordinamento' => 2]);
        $v3 = ChecklistVoce::create(['checklist_template_id' => $template->id, 'etichetta' => 'V3', 'tipo' => 'si_no', 'ordinamento' => 3]);
        $v4 = ChecklistVoce::create(['checklist_template_id' => $template->id, 'etichetta' => 'V4', 'tipo' => 'si_no', 'ordinamento' => 4]);

        $compilata = ChecklistCompilata::create([
            'checklist_template_id' => $template->id,
            'commessa_id'           => $this->commessa->id,
            'user_id'               => $this->meccanico->id,
        ]);

        // 2 risposte su 4 = 50%
        ChecklistRisposta::create(['checklist_compilata_id' => $compilata->id, 'checklist_voce_id' => $v1->id, 'valore_booleano' => true]);
        ChecklistRisposta::create(['checklist_compilata_id' => $compilata->id, 'checklist_voce_id' => $v2->id, 'valore_booleano' => false]);

        $this->assertEquals(50, $compilata->percentualeCompletamento());
    }

    /** Le route del tablet sono accessibili agli utenti con ruolo corretto */
    public function test_checklist_route_accesso_meccanico(): void
    {
        $template = ChecklistTemplate::create(['nome' => 'Accesso', 'attivo' => true, 'ordinamento' => 1]);

        $this->actingAs($this->meccanico);

        $response = $this->get(route('officina.checklist', [
            'commessa' => $this->commessa->id,
            'template' => $template->id,
        ]));

        $response->assertStatus(200);
    }

    /** Un accettatore non può accedere alla route checklist tablet */
    public function test_accettatore_non_accede_checklist_tablet(): void
    {
        $accettatore = User::factory()->create();
        $accettatore->assignRole('accettatore');

        $template = ChecklistTemplate::create(['nome' => 'T', 'attivo' => true, 'ordinamento' => 1]);

        $this->actingAs($accettatore);

        $response = $this->get(route('officina.checklist', [
            'commessa' => $this->commessa->id,
            'template' => $template->id,
        ]));

        $response->assertStatus(403);
    }

    /** La route impostazioni checklist è accessibile solo all'admin */
    public function test_impostazioni_checklist_solo_admin(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('impostazioni.checklist'))->assertStatus(200);

        $this->actingAs($this->meccanico);
        $this->get(route('impostazioni.checklist'))->assertStatus(403);
    }
}
