<?php

namespace Tests\Unit;

use App\Actions\WorkOrders\BulkChangeWorkOrderStatusAction;
use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class BulkChangeWorkOrderStatusActionTest extends TestCase
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
        $this->cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Unit', 'cognome' => 'Test']);
        $this->veicolo = Veicolo::create(['tipo' => 'auto', 'targa' => 'UNT001', 'marca' => 'T', 'modello' => 'M', 'alimentazione' => 'benzina']);
    }

    private function makeCommessa(StatoCommessa $stato): Commessa
    {
        static $n = 0;
        $n++;
        return Commessa::create([
            'numero'              => 'UNIT-' . $n,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'meccanica',
            'stato'               => $stato,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Unit test',
            'user_id'             => $this->admin->id,
        ]);
    }

    public function test_allowed_transitions_succeed(): void
    {
        $c1 = $this->makeCommessa(StatoCommessa::Bozza);
        $c2 = $this->makeCommessa(StatoCommessa::Bozza);

        /** @var BulkChangeWorkOrderStatusAction $action */
        $action = app(BulkChangeWorkOrderStatusAction::class);
        $result = $action->execute([$c1->id, $c2->id], StatoCommessa::Accettata, $this->admin);

        $this->assertCount(2, $result['success']);
        $this->assertEmpty($result['skipped']);
        $this->assertEquals(StatoCommessa::Accettata, $c1->fresh()->stato);
    }

    public function test_forbidden_transitions_are_skipped_not_rolled_back(): void
    {
        $ok    = $this->makeCommessa(StatoCommessa::Bozza);
        $noGo  = $this->makeCommessa(StatoCommessa::Consegnata);  // cannot go to Accettata

        $action = app(BulkChangeWorkOrderStatusAction::class);
        $result = $action->execute([$ok->id, $noGo->id], StatoCommessa::Accettata, $this->admin);

        $this->assertContains($ok->id, $result['success']);
        $this->assertCount(1, $result['skipped']);
        $this->assertEquals($noGo->id, $result['skipped'][0]['id']);
        $this->assertEquals(StatoCommessa::Accettata, $ok->fresh()->stato);
        $this->assertEquals(StatoCommessa::Consegnata, $noGo->fresh()->stato);  // untouched
    }

    public function test_per_record_log_created(): void
    {
        $c = $this->makeCommessa(StatoCommessa::Bozza);

        app(BulkChangeWorkOrderStatusAction::class)->execute([$c->id], StatoCommessa::Accettata, $this->admin, 'test nota');

        $this->assertDatabaseHas('commessa_log', [
            'commessa_id' => $c->id,
            'stato_da'    => StatoCommessa::Bozza->value,
            'stato_a'     => StatoCommessa::Accettata->value,
            'nota'        => 'test nota',
        ]);
    }
}
