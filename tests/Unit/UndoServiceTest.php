<?php

namespace Tests\Unit;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\User;
use App\Services\Undo\UndoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UndoServiceTest extends TestCase
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

    private function createUndoableActivity(): Activity
    {
        $movimento = app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 3,
            utente: $this->user,
        );

        return Activity::where('subject_type', \App\Models\MovimentoMagazzino::class)
            ->where('subject_id', $movimento->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function test_can_undo_within_window(): void
    {
        $activity = $this->createUndoableActivity();

        $this->assertTrue($this->service->canUndo($activity, $this->user));
    }

    public function test_cannot_undo_outside_window(): void
    {
        $activity = $this->createUndoableActivity();
        $activity->created_at = now()->subMinutes(20);
        $activity->save();

        $this->assertFalse($this->service->canUndo($activity, $this->user));
    }

    public function test_cannot_undo_already_undone(): void
    {
        $activity = $this->createUndoableActivity();
        $activity->properties = $activity->properties->merge(['undone_at' => now()->toIso8601String()]);
        $activity->save();

        $this->assertFalse($this->service->canUndo($activity, $this->user));
    }

    public function test_cannot_undo_non_undoable(): void
    {
        $movimento = app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::Carico,
            quantita: 3,
            utente: $this->user,
        );
        $activity = Activity::where('subject_type', \App\Models\MovimentoMagazzino::class)
            ->where('subject_id', $movimento->id)
            ->latest('id')
            ->firstOrFail();

        $activity->properties = collect(['undoable' => false]);
        $activity->save();

        $this->assertFalse($this->service->canUndo($activity, $this->user));
    }

    public function test_other_user_cannot_undo_without_permission(): void
    {
        $other = User::factory()->create();
        $other->assignRole('meccanico');

        $activity = $this->createUndoableActivity();

        $this->assertFalse($this->service->canUndo($activity, $other));
    }

    public function test_admin_can_undo_other_users_activity(): void
    {
        $other = User::factory()->create();
        $other->assignRole('admin');

        $activity = $this->createUndoableActivity();

        $this->assertTrue($this->service->canUndo($activity, $other));
    }

    public function test_undo_marks_activity_undone(): void
    {
        $activity = $this->createUndoableActivity();
        $giacenzaBefore = $this->articolo->fresh()->giacenza_attuale;

        $this->service->undo($activity, $this->user);

        $activity->refresh();
        $this->assertArrayHasKey('undone_at', $activity->properties->toArray());
        $this->assertEquals($this->user->id, $activity->properties['undone_by']);
    }

    public function test_undo_stock_restores_giacenza(): void
    {
        $giacenzaIniziale = $this->articolo->giacenza_attuale; // 10
        $activity = $this->createUndoableActivity(); // +3 → 13

        $this->service->undo($activity, $this->user); // -3 → 10

        $this->assertEquals($giacenzaIniziale, $this->articolo->fresh()->giacenza_attuale);
    }

    public function test_double_undo_throws(): void
    {
        $activity = $this->createUndoableActivity();
        $this->service->undo($activity, $this->user);

        $this->expectException(RuntimeException::class);
        $this->service->undo($activity->fresh(), $this->user);
    }

    public function test_concurrent_undo_blocked(): void
    {
        $activity = $this->createUndoableActivity();

        // Simulate first undo marking undone_at
        $activity->properties = $activity->properties->merge(['undone_at' => now()->toIso8601String(), 'undone_by' => $this->user->id]);
        $activity->save();

        $this->expectException(RuntimeException::class);
        $this->service->undo($activity->fresh(), $this->user);
    }
}
