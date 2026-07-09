<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Magazzino\ListaArticoli;
use App\Models\Articolo;
use App\Models\Fornitore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartsBulkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function makeArticolo(array $attrs = []): Articolo
    {
        static $n = 0;
        $n++;
        return Articolo::create(array_merge([
            'codice'           => 'ART-' . $n,
            'descrizione'      => 'Articolo ' . $n,
            'unita_misura'     => 'pz',
            'prezzo_acquisto'  => 10,
            'prezzo_vendita'   => 20,
            'iva_percentuale'  => 22,
            'scorta_minima'    => 5,
            'giacenza_attuale' => 10,
            'attivo'           => true,
        ], $attrs));
    }

    public function test_bulk_update_location(): void
    {
        $a1 = $this->makeArticolo(['ubicazione' => 'A1']);
        $a2 = $this->makeArticolo(['ubicazione' => 'A2']);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->set('selectedIds', [$a1->id, $a2->id])
            ->call('apriBulkUbicazioneModal')
            ->set('bulkNuovaUbicazione', 'B3-S1')
            ->call('eseguiBulkUbicazione')
            ->assertHasNoErrors()
            ->assertSet('showBulkUbicazioneModal', false);

        $this->assertEquals('B3-S1', $a1->fresh()->ubicazione);
        $this->assertEquals('B3-S1', $a2->fresh()->ubicazione);
    }

    public function test_bulk_reorder_creates_po_draft_grouped_by_supplier(): void
    {
        $fornitore = Fornitore::create(['ragione_sociale' => 'Fornitore Test', 'tipo' => 'fornitore']);

        $a1 = $this->makeArticolo(['fornitore_id' => $fornitore->id, 'giacenza_attuale' => 2, 'scorta_minima' => 5, 'scorta_massima' => 20]);
        $a2 = $this->makeArticolo(['fornitore_id' => $fornitore->id, 'giacenza_attuale' => 0, 'scorta_minima' => 3, 'scorta_massima' => 15]);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->set('selectedIds', [$a1->id, $a2->id])
            ->call('bulkRiordina')
            ->assertSet('showBulkReport', true);

        $this->assertDatabaseHas('ordini_fornitori', ['fornitore_id' => $fornitore->id]);
        $this->assertDatabaseCount('ordine_fornitore_righe', 2);
    }

    public function test_bulk_reorder_no_fornitore_reported(): void
    {
        $a = $this->makeArticolo(['fornitore_id' => null]);

        $component = Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->set('selectedIds', [$a->id])
            ->call('bulkRiordina')
            ->assertSet('showBulkReport', true);

        $report = $component->get('bulkReport');
        $this->assertContains($a->id, $report['senza_fornitore']);
    }

    public function test_inline_edit_ubicazione(): void
    {
        $art = $this->makeArticolo(['ubicazione' => 'X1']);

        $result = Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'ubicazione', 'Z9-S5');

        $this->assertEquals('Z9-S5', $art->fresh()->ubicazione);
    }

    public function test_inline_edit_logs_old_and_new(): void
    {
        $art = $this->makeArticolo(['ubicazione' => 'OLD']);

        Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'ubicazione', 'NEW');

        $log = \Spatie\Activitylog\Models\Activity::where('subject_type', Articolo::class)
            ->where('subject_id', $art->id)
            ->where('description', 'modifica_inline')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('OLD', $log->properties['old']['ubicazione']);
        $this->assertEquals('NEW', $log->properties['new']['ubicazione']);
    }

    public function test_inline_edit_rejects_unknown_field(): void
    {
        $art = $this->makeArticolo();

        $result = Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('salvaInlineEdit', $art->id, 'codice', 'HACKED');

        // codice not in allowed list → returns false, no change
        $this->assertEquals($art->codice, $art->fresh()->codice);
    }

    public function test_select_all_results_beyond_page(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->makeArticolo();
        }

        $component = Livewire::actingAs($this->admin)
            ->test(ListaArticoli::class)
            ->call('selectAllResults')
            ->assertSet('selectAll', true);

        // selectionCount is a #[Computed] property — verify via the instance method
        $this->assertEquals(30, $component->instance()->selectionCount());
    }
}
