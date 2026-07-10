<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Commesse\ListaCommesse;
use App\Models\User;
use App\Models\UserSavedFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SavedFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_save_and_apply_filter(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test(ListaCommesse::class)
            ->set('filtroStato', 'in_lavorazione')
            ->set('newFilterName', 'In lavorazione')
            ->call('saveCurrentFilters');

        $this->assertDatabaseHas('user_saved_filters', [
            'user_id'  => $user->id,
            'page_key' => 'work-orders.index',
            'name'     => 'In lavorazione',
        ]);
    }

    public function test_whitelist_prevents_injecting_extra_keys(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $filter = UserSavedFilter::create([
            'user_id'  => $user->id,
            'page_key' => 'work-orders.index',
            'name'     => 'Exploit',
            'filters'  => ['search' => 'ok', 'filtroStato' => 'bozza', 'injected' => 'evil'],
            'is_default' => false,
        ]);

        $component = Livewire::test(ListaCommesse::class)
            ->call('applySavedFilter', $filter->id);

        // injected key must not appear as a component property
        $this->assertNull($component->get('injected') ?? null);
        $this->assertEquals('ok', $component->get('search'));
        $this->assertEquals('bozza', $component->get('filtroStato'));
    }

    public function test_default_filter_applied_on_mount(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        UserSavedFilter::create([
            'user_id'    => $user->id,
            'page_key'   => 'work-orders.index',
            'name'       => 'Default test',
            'filters'    => ['search' => '', 'filtroStato' => 'completata', 'filtroTipo' => ''],
            'is_default' => true,
        ]);

        $component = Livewire::test(ListaCommesse::class);
        $this->assertEquals('completata', $component->get('filtroStato'));
    }

    public function test_filters_isolated_between_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        $this->actingAs($user1);

        Livewire::test(ListaCommesse::class)
            ->set('newFilterName', 'User1 Filter')
            ->call('saveCurrentFilters');

        $this->assertEquals(1, UserSavedFilter::where('user_id', $user1->id)->count());
        $this->assertEquals(0, UserSavedFilter::where('user_id', $user2->id)->count());
    }
}
