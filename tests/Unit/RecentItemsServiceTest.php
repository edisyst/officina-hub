<?php

namespace Tests\Unit;

use App\Models\Commessa;
use App\Models\User;
use App\Models\UserRecentItem;
use App\Services\Workspace\RecentItemsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentItemsServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecentItemsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecentItemsService::class);
    }

    public function test_track_creates_entry(): void
    {
        $user     = User::factory()->create();
        $commessa = Commessa::factory()->create();

        $this->service->track($user, $commessa);

        $this->assertDatabaseHas('user_recent_items', [
            'user_id'         => $user->id,
            'recordable_type' => Commessa::class,
            'recordable_id'   => $commessa->id,
            'visits'          => 1,
        ]);
    }

    public function test_track_upserts_and_increments_visits(): void
    {
        $user     = User::factory()->create();
        $commessa = Commessa::factory()->create();

        $this->service->track($user, $commessa);
        $this->service->track($user, $commessa);

        $this->assertDatabaseHas('user_recent_items', [
            'user_id'         => $user->id,
            'recordable_type' => Commessa::class,
            'recordable_id'   => $commessa->id,
            'visits'          => 2,
        ]);

        $this->assertDatabaseCount('user_recent_items', 1);
    }

    public function test_pruning_keeps_limit(): void
    {
        $user = User::factory()->create();
        $limit = config('workspace.recent_limit', 20);

        $commesse = Commessa::factory()->count($limit + 5)->create();

        foreach ($commesse as $c) {
            $this->service->track($user, $c);
        }

        $count = UserRecentItem::where('user_id', $user->id)->count();
        $this->assertLessThanOrEqual($limit, $count);
    }

    public function test_recent_returns_ordered_by_last_visited(): void
    {
        $user      = User::factory()->create();
        $commessa1 = Commessa::factory()->create();
        $commessa2 = Commessa::factory()->create();

        $this->service->track($user, $commessa1);

        UserRecentItem::where('user_id', $user->id)
            ->where('recordable_id', $commessa1->id)
            ->update(['last_visited_at' => now()->subHour()]);

        $this->service->track($user, $commessa2);

        $recent = $this->service->recent($user, 10);

        $this->assertCount(2, $recent);
        // commessa2 was visited more recently, so it must be first
        $this->assertStringContainsString((string) $commessa2->numero, $recent->first()['label']);
    }

    public function test_recent_isolated_between_users(): void
    {
        $user1    = User::factory()->create();
        $user2    = User::factory()->create();
        $commessa = Commessa::factory()->create();

        $this->service->track($user1, $commessa);

        $this->assertCount(1, $this->service->recent($user1, 10));
        $this->assertCount(0, $this->service->recent($user2, 10));
    }
}
