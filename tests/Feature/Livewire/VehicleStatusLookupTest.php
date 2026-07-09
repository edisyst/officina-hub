<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Livewire\VehicleStatus\Lookup;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleStatusLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Veicolo $veicolo;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::factory()->create([
            'tipo'    => TipoCliente::Fisica,
            'nome'    => 'Luigi',
            'cognome' => 'Ferrari',
        ]);

        $this->veicolo = Veicolo::factory()->create([
            'targa'      => 'LF123AB',
            'marca'      => 'Ferrari',
            'modello'    => '488',
            'cliente_id' => $this->cliente->id,
        ]);

        $this->veicolo->clienti()->attach($this->cliente->id);
    }

    public function test_componente_renderizza(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Lookup::class)
            ->assertStatus(200)
            ->assertSee('Targa, cognome o telefono');
    }

    public function test_nessun_risultato_con_meno_di_due_caratteri(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Lookup::class)
            ->set('ricerca', 'L')
            ->assertDontSee('LF123AB')
            ->assertSee('Digita almeno 2 caratteri');
    }

    public function test_mostra_card_con_ricerca_valida(): void
    {
        Commessa::factory()->create([
            'veicolo_id' => $this->veicolo->id,
            'cliente_id' => $this->cliente->id,
            'user_id'    => $this->admin->id,
            'stato'      => StatoCommessa::InLavorazione,
            'numero'     => 'OdL-TEST',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(Lookup::class)
            ->set('ricerca', 'LF1')
            ->assertSee('LF123AB')
            ->assertSee('Ferrari')
            ->assertSee('488')
            ->assertSee('Luigi Ferrari')
            ->assertSee('OdL-TEST');
    }

    public function test_espande_automaticamente_con_targa_esatta(): void
    {
        Commessa::factory()->create([
            'veicolo_id' => $this->veicolo->id,
            'cliente_id' => $this->cliente->id,
            'user_id'    => $this->admin->id,
            'stato'      => StatoCommessa::InLavorazione,
        ]);

        $this->actingAs($this->admin);

        // Targa esatta → autoExpandId impostato
        $component = Livewire::test(Lookup::class)
            ->set('ricerca', 'LF123AB');

        $component->assertSee('LF123AB');
        // auto-expand risolto in render, verifichiamo che il veicoloId sia nel DOM come expand target
        $component->assertSee((string) $this->veicolo->id);
    }

    public function test_nessun_dato_economico_visibile(): void
    {
        Commessa::factory()->create([
            'veicolo_id' => $this->veicolo->id,
            'cliente_id' => $this->cliente->id,
            'user_id'    => $this->admin->id,
            'stato'      => StatoCommessa::InLavorazione,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(Lookup::class)
            ->set('ricerca', 'LF1')
            ->assertDontSee('€')
            ->assertDontSee('margine')
            ->assertDontSee('imponibile');
    }

    public function test_link_apri_odl_presente(): void
    {
        Commessa::factory()->create([
            'veicolo_id' => $this->veicolo->id,
            'cliente_id' => $this->cliente->id,
            'user_id'    => $this->admin->id,
            'stato'      => StatoCommessa::InLavorazione,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(Lookup::class)
            ->set('ricerca', 'LF1')
            ->assertSee('Apri OdL');
    }

    public function test_rotta_richiede_autenticazione(): void
    {
        $this->get(route('stato-veicolo'))
            ->assertRedirect(route('login'));
    }

    public function test_rotta_accessibile_ad_utente_autenticato(): void
    {
        $this->actingAs($this->admin)
            ->get(route('stato-veicolo'))
            ->assertOk();
    }
}
