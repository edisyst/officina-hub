<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoCommessa;
use App\Enums\TipoCliente;
use App\Enums\TipoCommessa;
use App\Livewire\Recommendations\Panel;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\User;
use App\Models\VehicleRecommendation;
use App\Models\Veicolo;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecommendationsPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Commessa $commessa;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RuoliSeeder::class);
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        $cliente       = Cliente::create(['tipo' => TipoCliente::Fisica, 'nome' => 'Panel', 'cognome' => 'Test']);
        $this->veicolo = Veicolo::create(['marca' => 'Toyota', 'modello' => 'Yaris', 'km_attuali' => 30000, 'cliente_id' => $cliente->id]);
        $this->commessa = Commessa::create([
            'numero'              => 'PNL-001',
            'tipo'                => TipoCommessa::Meccanica->value,
            'stato'               => StatoCommessa::Bozza->value,
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'user_id'             => $this->user->id,
            'data_ingresso'       => now(),
            'km_ingresso'         => 30000,
            'descrizione_cliente' => '',
        ]);
    }

    public function test_panel_mostra_recommendation_pending(): void
    {
        VehicleRecommendation::create([
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'declined',
            'title'      => 'Pastiglie freno',
            'status'     => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(Panel::class, ['commessaId' => $this->commessa->id])
            ->assertSee('Pastiglie freno');
    }

    public function test_aggiungi_crea_riga_e_segna_accepted(): void
    {
        $rec = VehicleRecommendation::create([
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'mileage',
            'title'      => 'Tagliando olio',
            'status'     => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(Panel::class, ['commessaId' => $this->commessa->id])
            ->call('addToWorkOrder', $rec->id);

        $this->assertDatabaseHas('commessa_righe', [
            'commessa_id' => $this->commessa->id,
            'descrizione' => 'Tagliando olio',
        ]);

        $this->assertEquals('accepted', $rec->fresh()->status);
        $this->assertEquals($this->commessa->id, $rec->fresh()->resolved_work_order_id);
    }

    public function test_ignora_con_motivo_segna_dismissed(): void
    {
        $rec = VehicleRecommendation::create([
            'vehicle_id' => $this->veicolo->id,
            'source'     => 'deadline',
            'title'      => 'Revisione',
            'status'     => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(Panel::class, ['commessaId' => $this->commessa->id])
            ->call('openDismiss', $rec->id)
            ->set('dismissReason', 'Già fatta')
            ->call('confirmDismiss');

        $this->assertEquals('dismissed', $rec->fresh()->status);
        $this->assertEquals('Già fatta', $rec->fresh()->dismissed_reason);
    }
}
