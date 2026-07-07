<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatoCommessa;
use App\Livewire\Commesse\Board;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderBoardTest extends TestCase
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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cliente = Cliente::create(['tipo' => 'fisica', 'nome' => 'Test', 'cognome' => 'Board']);
        $this->veicolo = Veicolo::create([
            'tipo'          => 'auto',
            'targa'         => 'BO001RD',
            'marca'         => 'Test',
            'modello'       => 'Board',
            'alimentazione' => 'benzina',
        ]);
    }

    private function makeCommessa(StatoCommessa $stato, array $extra = []): Commessa
    {
        static $seq = 0;
        $seq++;

        return Commessa::create(array_merge([
            'numero'              => 'COM-TEST-' . $seq,
            'cliente_id'          => $this->cliente->id,
            'veicolo_id'          => $this->veicolo->id,
            'tipo'                => 'meccanica',
            'stato'               => $stato,
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test board',
            'user_id'             => $this->admin->id,
            'board_position'      => 1,
        ], $extra));
    }

    public function test_board_renders_all_columns(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(Board::class);

        foreach (StatoCommessa::cases() as $stato) {
            $component->assertSee($stato->label());
        }
    }

    public function test_board_shows_commessa_card(): void
    {
        $commessa = $this->makeCommessa(StatoCommessa::Accettata);

        $this->actingAs($this->admin);

        Livewire::test(Board::class)
            ->assertSee($commessa->numero);
    }

    public function test_moveCard_valid_transition_updates_stato_and_position(): void
    {
        $commessa = $this->makeCommessa(StatoCommessa::Accettata);

        $this->actingAs($this->admin);

        Livewire::test(Board::class)
            ->call('moveCard', $commessa->id, StatoCommessa::InLavorazione->value, [$commessa->id])
            ->assertSet('errorMessage', null);

        $commessa->refresh();
        $this->assertEquals(StatoCommessa::InLavorazione, $commessa->stato);
        $this->assertEquals(1, $commessa->board_position);
    }

    public function test_moveCard_invalid_transition_does_not_change_stato(): void
    {
        $commessa = $this->makeCommessa(StatoCommessa::Bozza);

        $this->actingAs($this->admin);

        Livewire::test(Board::class)
            ->call('moveCard', $commessa->id, StatoCommessa::Completata->value, [$commessa->id]);

        $commessa->refresh();
        $this->assertEquals(StatoCommessa::Bozza, $commessa->stato);
    }

    public function test_moveCard_invalid_transition_sets_error_message(): void
    {
        $commessa = $this->makeCommessa(StatoCommessa::Fatturata);

        $this->actingAs($this->admin);

        $component = Livewire::test(Board::class)
            ->call('moveCard', $commessa->id, StatoCommessa::Bozza->value, [$commessa->id]);

        $errorMessage = $component->get('errorMessage');
        $this->assertNotNull($errorMessage);
        $this->assertStringContainsString('Transizione non ammessa', $errorMessage);
    }

    public function test_reorder_same_column_updates_positions(): void
    {
        $c1 = $this->makeCommessa(StatoCommessa::Bozza, ['board_position' => 1]);
        $c2 = $this->makeCommessa(StatoCommessa::Bozza, ['board_position' => 2]);

        $this->actingAs($this->admin);

        // Move c2 to position 1 (before c1)
        Livewire::test(Board::class)
            ->call('moveCard', $c2->id, StatoCommessa::Bozza->value, [$c2->id, $c1->id]);

        $c1->refresh();
        $c2->refresh();
        $this->assertEquals(1, $c2->board_position);
        $this->assertEquals(2, $c1->board_position);
    }

    public function test_board_triggers_same_action_log_as_rest_of_app(): void
    {
        $commessa = $this->makeCommessa(StatoCommessa::Accettata);

        $this->actingAs($this->admin);

        Livewire::test(Board::class)
            ->call('moveCard', $commessa->id, StatoCommessa::InLavorazione->value, [$commessa->id]);

        $this->assertDatabaseHas('commessa_log', [
            'commessa_id' => $commessa->id,
            'stato_da'    => StatoCommessa::Accettata->value,
            'stato_a'     => StatoCommessa::InLavorazione->value,
        ]);
    }

    public function test_no_n_plus_one_queries(): void
    {
        // Create 20 commesse across different states
        for ($i = 1; $i <= 5; $i++) {
            $this->makeCommessa(StatoCommessa::Bozza, ['board_position' => $i]);
            $this->makeCommessa(StatoCommessa::Accettata, ['board_position' => $i]);
            $this->makeCommessa(StatoCommessa::InLavorazione, ['board_position' => $i]);
            $this->makeCommessa(StatoCommessa::Completata, ['board_position' => $i]);
        }

        $this->actingAs($this->admin);

        \DB::enableQueryLog();
        Livewire::test(Board::class);
        $queryCount = count(\DB::getQueryLog());
        \DB::flushQueryLog();

        // With eager loading and single base query, total should be well under 30
        // (1 commesse query + 4 eager loads + auth/session + meccanici + overhead)
        $this->assertLessThanOrEqual(30, $queryCount,
            "Too many queries: {$queryCount}. Check for N+1 in Board render."
        );
    }
}
