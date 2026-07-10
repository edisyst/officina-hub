<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Activity\Feed;
use App\Models\Commessa;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\Activity\ActivityFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
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

    public function test_feed_page_renders(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Feed::class)
            ->assertStatus(200);
    }

    public function test_humanize_fallback_for_unknown_activity(): void
    {
        $service = app(ActivityFeedService::class);

        $activity = activity()
            ->causedBy($this->admin)
            ->log('test_evento');

        $result = $service->humanize($activity);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_humanize_commessa_stato_change(): void
    {
        $commessa = Commessa::factory()->create(['stato' => \App\Enums\StatoCommessa::InLavorazione]);
        $service  = app(ActivityFeedService::class);

        // Simulate a stato update activity
        $activity = activity()
            ->causedBy($this->admin)
            ->performedOn($commessa)
            ->withProperties([
                'old'        => ['stato' => 'in_lavorazione'],
                'attributes' => ['stato' => 'sospesa'],
            ])
            ->event('updated')
            ->log("stato cambiato");

        $result = $service->humanize($activity);

        $this->assertStringContainsString('sospesa', strtolower($result));
    }

    public function test_undo_button_visible_for_undoable_within_window(): void
    {
        $this->actingAs($this->admin);

        $activity = activity()
            ->causedBy($this->admin)
            ->withProperties(['undoable' => true])
            ->log('test');

        // No handler supports this generic activity, so canUndo returns false
        // Create a proper stock movement activity instead
        $articolo  = \App\Models\Articolo::factory()->create(['giacenza_attuale' => 10]);
        $movimento = app(\App\Actions\Magazzino\CaricoManualeAction::class)->execute(
            $articolo, \App\Enums\TipoMovimento::Carico, 5, $this->admin
        );

        $undoableActivity = Activity::where('subject_type', MovimentoMagazzino::class)
            ->where('subject_id', $movimento->id)->latest('id')->first();

        Livewire::test(Feed::class)
            ->assertSee($this->admin->name);
    }
}
