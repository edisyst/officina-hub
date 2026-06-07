<?php

namespace Tests\Feature;

use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Enums\TipoSinistro;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CompagniaAssicurativa;
use App\Models\DannoVeicolo;
use App\Models\Perizia;
use App\Models\Sinistro;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\PdfService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfCarrozzeriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $cliente = Cliente::create([
            'tipo'    => 'fisica',
            'nome'    => 'Luca',
            'cognome' => 'Bianchi',
        ]);

        $veicolo = Veicolo::create([
            'marca'   => 'Toyota',
            'modello' => 'Yaris',
            'tipo'    => 'auto',
            'targa'   => 'AB123CD',
        ]);
        $veicolo->clienti()->attach($cliente->id);

        $this->commessa = Commessa::create([
            'numero'              => 'C/2026/002',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'tipo'                => TipoCommessa::Carrozzeria,
            'stato'               => StatoCommessa::Consegnata,
            'data_ingresso'       => now(),
            'user_id'             => $this->admin->id,
            'descrizione_cliente' => 'Danno kasko test',
        ]);

        $compagnia = CompagniaAssicurativa::create(['nome' => 'Test Assicurazioni']);

        $sinistro = Sinistro::create([
            'commessa_id'    => $this->commessa->id,
            'tipo_sinistro'  => TipoSinistro::Kasko,
            'numero_sinistro'=> 'KASKO-001',
            'stato'          => 'chiuso',
        ]);

        Perizia::create([
            'sinistro_id'             => $sinistro->id,
            'importo_liquidato'       => 1500,
            'importo_franchigia'      => 200,
            'importo_netto_liquidato' => 1300,
            'accettata'               => true,
        ]);

        DannoVeicolo::create([
            'commessa_id'    => $this->commessa->id,
            'zona'           => 'anteriore_centro',
            'tipo_danno'     => 'ammaccatura',
            'descrizione'    => 'Ammaccatura sul cofano',
            'quantita'       => 1,
            'prezzo_stimato' => 800,
            'prezzo_perizia' => 750,
        ]);
    }

    public function test_genera_pdf_carrozzeria(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('pdf.carrozzeria', $this->commessa->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_carrozzeria_include_dati_sinistro(): void
    {
        $this->actingAs($this->admin);

        $service = app(PdfService::class);

        // Il metodo non deve lanciare eccezioni
        $this->commessa->load(['cliente', 'veicolo', 'user', 'sinistro.compagniaAssicurativa', 'sinistro.perizia', 'danni']);

        $response = $service->schedaCarrozzeria($this->commessa);

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('carrozzeria', $response->headers->get('Content-Disposition'));
    }

    public function test_route_pdf_carrozzeria_richiede_autenticazione(): void
    {
        $response = $this->get(route('pdf.carrozzeria', $this->commessa->id));
        $response->assertRedirect(route('login'));
    }
}
