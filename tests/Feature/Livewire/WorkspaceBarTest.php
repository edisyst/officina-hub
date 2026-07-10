<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Workspace\WorkspaceBar;
use App\Models\User;
use App\Models\UserShortcut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceBarTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_toggle_saves_shortcut(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test(WorkspaceBar::class, ['url' => '/commesse', 'title' => 'Commesse'])
            ->call('toggle')
            ->assertSet('showLabelPopover', true)
            ->set('editLabel', 'Commesse')
            ->call('saveShortcut');

        $this->assertDatabaseHas('user_shortcuts', [
            'user_id' => $user->id,
            'url'     => '/commesse',
            'label'   => 'Commesse',
        ]);
    }

    public function test_toggle_removes_existing_shortcut(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        UserShortcut::create([
            'user_id'  => $user->id,
            'url'      => '/commesse',
            'label'    => 'Commesse',
            'icon'     => 'fas fa-star',
            'position' => 0,
        ]);

        Livewire::test(WorkspaceBar::class, ['url' => 'http://localhost/commesse', 'title' => 'Commesse'])
            ->assertSet('isStarred', true)
            ->call('toggle');

        $this->assertDatabaseMissing('user_shortcuts', [
            'user_id' => $user->id,
            'url'     => '/commesse',
        ]);
    }

    public function test_shortcuts_isolated_per_user(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        $this->actingAs($user1);

        Livewire::test(WorkspaceBar::class, ['url' => '/commesse', 'title' => 'Commesse'])
            ->set('editLabel', 'Test')
            ->call('saveShortcut');

        $this->assertEquals(0, UserShortcut::where('user_id', $user2->id)->count());
    }
}
