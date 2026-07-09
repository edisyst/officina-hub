<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoCommessa;
use App\Livewire\Commesse\ListaCommesse;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrdersBulkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->admin   = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Bulk', 'cognome' => 'Test']);
        $this->veicolo = Veicolo::create(['tipo' => 'auto', 'targa' => 'BLK001', 'marca' => 'T', 'modello' => 'M', 'alimentazione' => 'benzina']);
    }

    private function makeCommessa(StatoCommessa $stato, string $suffix = ''): Commessa
    {
        static $n = 0;
        $n++;
        return Commessa::create([
            'numero'              => 'BLK-' . $n . $suffix,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'meccanica',
            'stato'               => $stato,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test',
            'user_id'             => $this->admin->id,
        ]);
    }

    public function test_bulk_stato_successes_and_skips_per_record(): void
    {
        $bozza1      = $this->makeCommessa(StatoCommessa::Bozza);
        $bozza2      = $this->makeCommessa(StatoCommessa::Bozza);
        $inLav       = $this->makeCommessa(StatoCommessa::InLavorazione);  // cannot go to Accettata

        Livewire::actingAs($this->admin)
            ->test(ListaCommesse::class)
            ->set('selectedIds', [$bozza1->id, $bozza2->id, $inLav->id])
            ->call('apriBulkStatoModal')
            ->set('bulkStatoTarget', StatoCommessa::Accettata->value)
            ->call('eseguiBulkCambioStato')
            ->assertSet('showBulkReport', true)
            ->assertSet('showBulkStatoModal', false);

        // Verify state changes
        $this->assertEquals(StatoCommessa::Accettata, $bozza1->fresh()->stato);
        $this->assertEquals(StatoCommessa::Accettata, $bozza2->fresh()->stato);
        $this->assertEquals(StatoCommessa::InLavorazione, $inLav->fresh()->stato);  // unchanged
    }

    public function test_select_all_results_spans_beyond_current_page(): void
    {
        // Create 25 bozze (more than 1 page of 20)
        for ($i = 0; $i < 25; $i++) {
            $this->makeCommessa(StatoCommessa::Bozza);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(ListaCommesse::class)
            ->call('selectAllResults')
            ->assertSet('selectAll', true);

        // selectionCount should be 25, not 20 (page size)
        $this->assertEquals(25, $component->instance()->selectionCount());
    }

    public function test_deselect_all_clears_state(): void
    {
        $c = $this->makeCommessa(StatoCommessa::Bozza);

        Livewire::actingAs($this->admin)
            ->test(ListaCommesse::class)
            ->set('selectedIds', [$c->id])
            ->call('deselectAll')
            ->assertSet('selectedIds', [])
            ->assertSet('selectAll', false)
            ->assertSet('selectPage', false);
    }

    public function test_bulk_stato_logs_activity(): void
    {
        $c = $this->makeCommessa(StatoCommessa::Bozza);

        Livewire::actingAs($this->admin)
            ->test(ListaCommesse::class)
            ->set('selectedIds', [$c->id])
            ->call('apriBulkStatoModal')
            ->set('bulkStatoTarget', StatoCommessa::Accettata->value)
            ->call('eseguiBulkCambioStato');

        // CommessaLog entry should exist
        $this->assertDatabaseHas('commessa_log', [
            'commessa_id' => $c->id,
            'stato_a'     => StatoCommessa::Accettata->value,
        ]);
    }

    public function test_non_admin_cannot_bulk_change_stato(): void
    {
        $meccanico = User::factory()->create();
        $meccanico->assignRole('meccanico');

        $c = $this->makeCommessa(StatoCommessa::Bozza);

        Livewire::actingAs($meccanico)
            ->test(ListaCommesse::class)
            ->set('selectedIds', [$c->id])
            ->call('apriBulkStatoModal')
            ->assertStatus(403);
    }
}
