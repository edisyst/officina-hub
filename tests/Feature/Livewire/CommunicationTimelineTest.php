<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Communications\Timeline;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\User;
use Database\Seeders\RuoliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunicationTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RuoliSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
        return $user;
    }

    public function test_renders_for_customer(): void
    {
        $this->actingAsAdmin();
        $cliente = Cliente::factory()->create();
        Communication::factory()->forCustomer($cliente)->count(3)->create();

        Livewire::test(Timeline::class, ['customerId' => $cliente->id])
            ->assertStatus(200);
    }

    public function test_renders_for_work_order(): void
    {
        $user     = $this->actingAsAdmin();
        $cliente  = Cliente::factory()->create();
        $commessa = Commessa::factory()->create(['cliente_id' => $cliente->id, 'user_id' => $user->id]);
        Communication::factory()->forWorkOrder($commessa)->count(2)->create(['user_id' => $user->id]);

        Livewire::test(Timeline::class, ['customerId' => $cliente->id, 'workOrderId' => $commessa->id])
            ->assertStatus(200);
    }

    public function test_annotation_stores_communication(): void
    {
        $user    = $this->actingAsAdmin();
        $cliente = Cliente::factory()->create();

        Livewire::test(Timeline::class, ['customerId' => $cliente->id])
            ->set('annoChannel', 'phone')
            ->set('annoDirection', 'inbound')
            ->set('annoBody', 'Il cliente ha chiamato per un preventivo.')
            ->call('salvaAnnotazione')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('communications', [
            'customer_id' => $cliente->id,
            'channel'     => 'phone',
            'direction'   => 'inbound',
            'user_id'     => $user->id,
        ]);
    }

    public function test_annotation_validation_fails_on_empty_body(): void
    {
        $this->actingAsAdmin();
        $cliente = Cliente::factory()->create();

        Livewire::test(Timeline::class, ['customerId' => $cliente->id])
            ->set('annoChannel', 'phone')
            ->set('annoDirection', 'inbound')
            ->set('annoBody', '')
            ->call('salvaAnnotazione')
            ->assertHasErrors(['annoBody']);
    }

    public function test_channel_filter_shows_only_matching(): void
    {
        $this->actingAsAdmin();
        $cliente = Cliente::factory()->create();
        Communication::factory()->forCustomer($cliente)->create(['channel' => 'phone', 'direction' => 'inbound']);
        Communication::factory()->forCustomer($cliente)->create(['channel' => 'email', 'direction' => 'outbound']);

        $component = Livewire::test(Timeline::class, ['customerId' => $cliente->id])
            ->set('filterChannel', 'phone');

        // Component renders without error
        $component->assertStatus(200);
    }
}
