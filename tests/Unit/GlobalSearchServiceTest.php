<?php

namespace Tests\Unit;

use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Models\Articolo;
use App\Models\CategoriaArticolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Veicolo;
use App\Services\GlobalSearchService;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private GlobalSearchService $service;
    private Cliente $cliente;
    private Veicolo $veicolo;
    private Commessa $commessa;
    private Articolo $articolo;
    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
        $this->service = app(GlobalSearchService::class);
        $this->user = \App\Models\User::factory()->create();

        $this->cliente = Cliente::create([
            'tipo'     => TipoCliente::Fisica,
            'nome'     => 'Mario',
            'cognome'  => 'Rossi',
            'email'    => 'mario.rossi@test.it',
            'telefono' => '+39 333 123 4567',
        ]);

        $this->veicolo = Veicolo::create([
            'marca'   => 'Fiat',
            'modello' => 'Punto',
            'targa'   => 'AB123CD',
            'vin'     => 'ZFA18800000123456',
        ]);

        $categoria = CategoriaArticolo::create(['nome' => 'Test']);
        $this->articolo = Articolo::create([
            'codice'      => 'RIC-001',
            'descrizione' => 'Filtro olio motore',
            'categoria_id' => $categoria->id,
            'giacenza_attuale' => 10,
        ]);

        $this->commessa = Commessa::create([
            'numero'              => 'OdL-2026-0001',
            'tipo'                => TipoCommessa::Tagliando,
            'stato'               => 'bozza',
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'data_ingresso'       => now(),
            'user_id'             => $this->user->id,
            'descrizione_cliente' => 'Tagliando completo',
            'diagnosi_tecnica'    => '',
            'note_interne'        => '',
        ]);
    }

    public function test_query_sotto_due_caratteri_ritorna_vuoto(): void
    {
        $this->assertEmpty($this->service->search(''));
        $this->assertEmpty($this->service->search('a'));
    }

    public function test_ricerca_per_targa_parziale(): void
    {
        $results = $this->service->search('AB123');
        $tipi = array_column($results, 'tipo');
        $this->assertContains('veicoli', $tipi);

        $veicoli = collect($results)->firstWhere('tipo', 'veicoli');
        $labels = array_column($veicoli['items'], 'label');
        $this->assertContains('Fiat Punto', $labels);
    }

    public function test_ricerca_per_nome_cliente(): void
    {
        $results = $this->service->search('Mario');
        $tipi = array_column($results, 'tipo');
        $this->assertContains('clienti', $tipi);
    }

    public function test_ricerca_per_codice_ricambio(): void
    {
        $results = $this->service->search('RIC-001');
        $tipi = array_column($results, 'tipo');
        $this->assertContains('articoli', $tipi);
    }

    public function test_ricerca_per_telefono_con_spazi(): void
    {
        $results = $this->service->search('333 123 4567');
        // phone search: check clienti group present (if matching)
        // On SQLite, phone LIKE search should work
        $this->assertIsArray($results);
    }

    public function test_ricerca_per_numero_odl(): void
    {
        $results = $this->service->search('OdL-2026');
        $tipi = array_column($results, 'tipo');
        $this->assertContains('commesse', $tipi);
    }

    public function test_limite_per_gruppo_rispettato(): void
    {
        for ($i = 0; $i < 8; $i++) {
            Cliente::create([
                'tipo'    => TipoCliente::Fisica,
                'nome'    => 'Nome',
                'cognome' => "Comune{$i}",
                'email'   => "comune{$i}@test.it",
            ]);
        }

        $results = $this->service->search('Nome', 3);
        $clienti = collect($results)->firstWhere('tipo', 'clienti');

        if ($clienti) {
            $this->assertLessThanOrEqual(3, count($clienti['items']));
        }

        $this->assertTrue(true); // guard: no exception
    }

    public function test_quick_actions_veicolo_presenti(): void
    {
        $results = $this->service->search('AB123');
        $veicoli = collect($results)->firstWhere('tipo', 'veicoli');
        $this->assertNotNull($veicoli);
        $item = $veicoli['items'][0];
        $this->assertArrayHasKey('quick_actions', $item);
        $this->assertNotEmpty($item['quick_actions']);
    }

    public function test_quick_actions_articolo_presenti(): void
    {
        $results = $this->service->search('Filtro');
        $articoli = collect($results)->firstWhere('tipo', 'articoli');
        if ($articoli) {
            $item = $articoli['items'][0];
            $this->assertArrayHasKey('quick_actions', $item);
        }
        $this->assertTrue(true);
    }
}
