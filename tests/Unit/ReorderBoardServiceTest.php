<?php

namespace Tests\Unit;

use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use App\Services\Commesse\ReorderBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReorderBoardServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReorderBoardService $service;
    private User $user;
    private Cliente $cliente;
    private Veicolo $veicolo;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        $this->cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'T', 'cognome' => 'R']);
        $this->veicolo = Veicolo::create([
            'tipo'          => 'auto',
            'targa'         => 'RS001TB',
            'marca'         => 'T',
            'modello'       => 'R',
            'alimentazione' => 'benzina',
        ]);

        $this->service = new ReorderBoardService();
    }

    private function makeCommessa(StatoCommessa $stato, int $pos): Commessa
    {
        static $seq = 0;
        $seq++;

        return Commessa::create([
            'numero'              => 'COM-RST-' . $seq,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'meccanica',
            'stato'               => $stato,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test',
            'user_id'             => $this->user->id,
            'board_position'      => $pos,
        ]);
    }

    public function test_riordina_assigns_compact_positions(): void
    {
        $c1 = $this->makeCommessa(StatoCommessa::Bozza, 1);
        $c2 = $this->makeCommessa(StatoCommessa::Bozza, 2);
        $c3 = $this->makeCommessa(StatoCommessa::Bozza, 3);

        // Reverse order
        $this->service->riordina(StatoCommessa::Bozza, [$c3->id, $c2->id, $c1->id]);

        $this->assertEquals(1, $c3->fresh()->board_position);
        $this->assertEquals(2, $c2->fresh()->board_position);
        $this->assertEquals(3, $c1->fresh()->board_position);
    }

    public function test_riordina_insert_in_middle(): void
    {
        $c1 = $this->makeCommessa(StatoCommessa::Bozza, 1);
        $c2 = $this->makeCommessa(StatoCommessa::Bozza, 2);
        $c3 = $this->makeCommessa(StatoCommessa::Bozza, 3);

        // Move c3 between c1 and c2
        $this->service->riordina(StatoCommessa::Bozza, [$c1->id, $c3->id, $c2->id]);

        $this->assertEquals(1, $c1->fresh()->board_position);
        $this->assertEquals(2, $c3->fresh()->board_position);
        $this->assertEquals(3, $c2->fresh()->board_position);
    }

    public function test_riordina_does_not_affect_other_columns(): void
    {
        $bozza = $this->makeCommessa(StatoCommessa::Bozza, 1);
        $accettata = $this->makeCommessa(StatoCommessa::Accettata, 99);

        $this->service->riordina(StatoCommessa::Bozza, [$bozza->id]);

        $this->assertEquals(99, $accettata->fresh()->board_position);
    }

    public function test_riordina_empty_array_does_nothing(): void
    {
        $c1 = $this->makeCommessa(StatoCommessa::Bozza, 5);

        $this->service->riordina(StatoCommessa::Bozza, []);

        $this->assertEquals(5, $c1->fresh()->board_position);
    }
}
