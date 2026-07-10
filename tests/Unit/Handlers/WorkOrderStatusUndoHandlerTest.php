<?php

namespace Tests\Unit\Handlers;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Models\User;
use App\Services\Undo\Handlers\WorkOrderStatusUndoHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderStatusUndoHandlerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WorkOrderStatusUndoHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
        $this->handler = app(WorkOrderStatusUndoHandler::class);
    }

    private function commessa(StatoCommessa $stato): Commessa
    {
        return Commessa::factory()->create(['stato' => $stato]);
    }

    private function transisci(Commessa $commessa, StatoCommessa $nuovo): ?int
    {
        return app(AggiornaStatoAction::class)->execute($commessa, $nuovo, $this->user);
    }

    public function test_supports_commessa_stato_updated(): void
    {
        $c = $this->commessa(StatoCommessa::InLavorazione);
        $this->transisci($c, StatoCommessa::Sospesa);

        $act = Activity::where('subject_type', Commessa::class)->where('subject_id', $c->id)->latest('id')->first();
        $this->assertTrue($this->handler->supports($act));
    }

    public function test_undo_sospesa_back_to_in_lavorazione(): void
    {
        $c = $this->commessa(StatoCommessa::InLavorazione);
        $this->transisci($c, StatoCommessa::Sospesa);

        $act = Activity::where('subject_type', Commessa::class)->where('subject_id', $c->id)->latest('id')->firstOrFail();
        $this->handler->undo($act, $this->user);

        $this->assertEquals(StatoCommessa::InLavorazione, $c->fresh()->stato);
    }

    public function test_undo_forbidden_for_irreversible_transition(): void
    {
        $c = $this->commessa(StatoCommessa::Accettata);
        $this->transisci($c, StatoCommessa::InLavorazione);

        $act = Activity::where('subject_type', Commessa::class)->where('subject_id', $c->id)->latest('id')->firstOrFail();

        $this->expectException(\Throwable::class);
        $this->handler->undo($act, $this->user);
    }
}
