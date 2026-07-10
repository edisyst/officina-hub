<?php

namespace Tests\Feature;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\Undo\UndoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UndoConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UndoService $service;
    private Articolo $articolo;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->user    = User::factory()->create();
        $this->user->assignRole('admin');
        $this->service = app(UndoService::class);
        $this->actingAs($this->user);

        $this->articolo = Articolo::factory()->create(['giacenza_attuale' => 10]);
    }

    private function createUndoableMovimento(): Activity
    {
        $m = app(CaricoManualeAction::class)->execute(
            $this->articolo, TipoMovimento::Carico, 5, $this->user
        );
        return Activity::where('subject_type', MovimentoMagazzino::class)
            ->where('subject_id', $m->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function test_concurrent_undo_second_call_throws(): void
    {
        $activity = $this->createUndoableMovimento();
        $giacenzaIniziale = $this->articolo->giacenza_attuale; // 10

        // First undo succeeds
        $this->service->undo($activity, $this->user);

        // giacenza tornata a 10
        $this->assertEquals($giacenzaIniziale, $this->articolo->fresh()->giacenza_attuale);

        // Second undo must fail (already undone)
        $this->expectException(RuntimeException::class);
        $this->service->undo($activity->fresh(), $this->user);
    }

    public function test_undo_giacenza_coherente_dopo_storno(): void
    {
        $giacenzaStart = $this->articolo->giacenza_attuale; // 10
        $activity = $this->createUndoableMovimento(); // +5 → 15

        $this->service->undo($activity, $this->user); // -5 → 10

        $this->assertDatabaseHas('articoli', [
            'id'               => $this->articolo->id,
            'giacenza_attuale' => $giacenzaStart,
        ]);
    }

    public function test_compensazione_not_marked_undoable(): void
    {
        $activity = $this->createUndoableMovimento();
        $this->service->undo($activity, $this->user);

        $activity->refresh();
        $compensazioneId = $activity->properties['compensazione_id'];
        $comp = Activity::findOrFail($compensazioneId);

        $this->assertFalse((bool) ($comp->properties['undoable'] ?? false));
    }
}
