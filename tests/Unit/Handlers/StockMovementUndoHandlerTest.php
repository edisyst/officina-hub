<?php

namespace Tests\Unit\Handlers;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\Undo\Handlers\StockMovementUndoHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockMovementUndoHandlerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Articolo $articolo;
    private StockMovementUndoHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
        $this->handler = app(StockMovementUndoHandler::class);

        $this->articolo = Articolo::factory()->create(['giacenza_attuale' => 10]);
    }

    private function carico(int $qty): Activity
    {
        $m = app(CaricoManualeAction::class)->execute($this->articolo, TipoMovimento::Carico, $qty, $this->user);
        return Activity::where('subject_type', MovimentoMagazzino::class)->where('subject_id', $m->id)->latest('id')->firstOrFail();
    }

    private function scarico(int $qty): Activity
    {
        $m = app(CaricoManualeAction::class)->execute($this->articolo, TipoMovimento::Scarico, $qty, $this->user);
        return Activity::where('subject_type', MovimentoMagazzino::class)->where('subject_id', $m->id)->latest('id')->firstOrFail();
    }

    public function test_supports_movimento_created(): void
    {
        $act = $this->carico(1);
        $this->assertTrue($this->handler->supports($act));
    }

    public function test_undo_carico_restores_giacenza(): void
    {
        $giacenzaIniziale = $this->articolo->giacenza_attuale; // 10
        $act = $this->carico(5); // 15

        $this->handler->undo($act, $this->user); // -5 → 10

        $this->assertEquals($giacenzaIniziale, $this->articolo->fresh()->giacenza_attuale);
    }

    public function test_undo_scarico_restores_giacenza(): void
    {
        $giacenzaIniziale = $this->articolo->giacenza_attuale; // 10
        $act = $this->scarico(3); // 7

        $this->handler->undo($act, $this->user); // +3 → 10

        $this->assertEquals($giacenzaIniziale, $this->articolo->fresh()->giacenza_attuale);
    }

    public function test_compensazione_activity_not_undoable(): void
    {
        $act = $this->carico(2);
        $comp = $this->handler->undo($act, $this->user);

        $this->assertFalse((bool) ($comp->properties['undoable'] ?? false));
    }
}
