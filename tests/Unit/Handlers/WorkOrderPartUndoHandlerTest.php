<?php

namespace Tests\Unit\Handlers;

use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\User;
use App\Services\Undo\Handlers\WorkOrderPartUndoHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderPartUndoHandlerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WorkOrderPartUndoHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
        $this->handler = app(WorkOrderPartUndoHandler::class);
    }

    private function creaRigaConActivity(Commessa $commessa): array
    {
        $riga = CommessaRiga::factory()->create(['commessa_id' => $commessa->id]);

        $activityId = activity('commessa_riga')
            ->causedBy($this->user)
            ->performedOn($riga)
            ->withProperties([
                'commessa_id'     => $commessa->id,
                'commessa_numero' => $commessa->numero,
                'descrizione'     => $riga->descrizione,
                'quantita'        => (float) $riga->quantita,
                'undoable'        => true,
            ])
            ->event('created')
            ->log('riga_aggiunta')
            ->id;

        return [$riga, Activity::findOrFail($activityId)];
    }

    public function test_supports_riga_created(): void
    {
        $commessa = Commessa::factory()->create();
        [, $act] = $this->creaRigaConActivity($commessa);
        $this->assertTrue($this->handler->supports($act));
    }

    public function test_undo_deletes_riga(): void
    {
        $commessa = Commessa::factory()->create();
        [$riga, $act] = $this->creaRigaConActivity($commessa);

        $this->handler->undo($act, $this->user);

        $this->assertNull(CommessaRiga::find($riga->id));
    }

    public function test_undo_creates_compensazione_activity(): void
    {
        $commessa = Commessa::factory()->create();
        [, $act] = $this->creaRigaConActivity($commessa);

        $comp = $this->handler->undo($act, $this->user);

        $this->assertEquals('compensazione', $comp->event);
        $this->assertEquals('commessa_riga', $comp->log_name);
    }
}
