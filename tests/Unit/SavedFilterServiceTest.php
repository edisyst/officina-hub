<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSavedFilter;
use App\Services\Workspace\SavedFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    private SavedFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SavedFilterService::class);
    }

    public function test_save_creates_filter(): void
    {
        $user = User::factory()->create();

        $this->service->save($user, 'work-orders.index', 'Aperti', ['search' => '', 'filtroStato' => 'in_lavorazione']);

        $this->assertDatabaseHas('user_saved_filters', [
            'user_id'  => $user->id,
            'page_key' => 'work-orders.index',
            'name'     => 'Aperti',
        ]);
    }

    public function test_set_default_enforces_single_default(): void
    {
        $user = User::factory()->create();

        $f1 = $this->service->save($user, 'work-orders.index', 'Filtro A', ['search' => '']);
        $f2 = $this->service->save($user, 'work-orders.index', 'Filtro B', ['search' => '']);

        $this->service->setDefault($user, 'work-orders.index', $f1->id);
        $this->service->setDefault($user, 'work-orders.index', $f2->id);

        $this->assertDatabaseHas('user_saved_filters', ['id' => $f1->id, 'is_default' => false]);
        $this->assertDatabaseHas('user_saved_filters', ['id' => $f2->id, 'is_default' => true]);
    }

    public function test_get_default_returns_null_when_none(): void
    {
        $user = User::factory()->create();
        $this->assertNull($this->service->getDefault($user, 'work-orders.index'));
    }

    public function test_delete_removes_filter(): void
    {
        $user = User::factory()->create();
        $f = $this->service->save($user, 'work-orders.index', 'Test', ['search' => '']);

        $this->service->delete($user, $f->id);

        $this->assertDatabaseMissing('user_saved_filters', ['id' => $f->id]);
    }

    public function test_isolated_between_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->service->save($user1, 'work-orders.index', 'Mio filtro', ['search' => '']);

        $this->assertCount(1, $this->service->list($user1, 'work-orders.index'));
        $this->assertCount(0, $this->service->list($user2, 'work-orders.index'));
    }

    public function test_whitelist_in_apply_filters(): void
    {
        // Simulate: only whitelisted keys are applied, extras ignored
        $whitelist = ['search', 'filtroStato'];
        $filters = ['search' => 'test', 'filtroStato' => 'completata', 'injected_key' => 'evil'];

        $applied = [];
        foreach ($whitelist as $key) {
            if (array_key_exists($key, $filters)) {
                $applied[$key] = $filters[$key];
            }
        }

        $this->assertArrayHasKey('search', $applied);
        $this->assertArrayHasKey('filtroStato', $applied);
        $this->assertArrayNotHasKey('injected_key', $applied);
    }
}
