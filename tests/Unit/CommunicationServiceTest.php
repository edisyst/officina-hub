<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\User;
use App\Services\Communications\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommunicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommunicationService::class);
    }

    public function test_log_manual_creates_communication(): void
    {
        $user     = User::factory()->create();
        $cliente  = Cliente::factory()->create();

        $comm = $this->service->logManual([
            'customer_id' => $cliente->id,
            'channel'     => 'phone',
            'direction'   => 'inbound',
            'body'        => 'Cliente ha chiamato per aggiornamento.',
        ], $user);

        $this->assertDatabaseHas('communications', [
            'id'          => $comm->id,
            'customer_id' => $cliente->id,
            'user_id'     => $user->id,
            'channel'     => 'phone',
            'direction'   => 'inbound',
        ]);
    }

    public function test_log_manual_uses_provided_occurred_at(): void
    {
        $user    = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $past    = now()->subDay()->startOfHour();

        $comm = $this->service->logManual([
            'customer_id' => $cliente->id,
            'channel'     => 'note',
            'direction'   => 'outbound',
            'body'        => 'Nota retroattiva.',
            'occurred_at' => $past,
        ], $user);

        $this->assertEquals($past->format('Y-m-d H:i:s'), $comm->occurred_at->format('Y-m-d H:i:s'));
    }

    public function test_timeline_for_customer_returns_all_communications(): void
    {
        $cliente = Cliente::factory()->create();
        Communication::factory()->forCustomer($cliente)->count(5)->create();
        Communication::factory()->count(3)->create(); // altri clienti

        $paginator = $this->service->timelineFor($cliente);

        $this->assertEquals(5, $paginator->total());
    }

    public function test_timeline_for_work_order_uses_context_scope(): void
    {
        $user     = User::factory()->create();
        $cliente  = Cliente::factory()->create();
        $commessa = Commessa::factory()->create(['cliente_id' => $cliente->id, 'user_id' => $user->id]);

        // Legate all'OdL
        Communication::factory()->forWorkOrder($commessa)->count(2)->create(['user_id' => $user->id]);
        // Del cliente senza OdL (devono apparire nel contesto OdL)
        Communication::factory()->forCustomer($cliente)->count(3)->create(['work_order_id' => null, 'user_id' => $user->id]);
        // Di altro cliente: non devono apparire
        Communication::factory()->count(2)->create();

        $paginator = $this->service->timelineFor($commessa);

        $this->assertEquals(5, $paginator->total());
    }

    public function test_log_automatic_is_idempotent(): void
    {
        $cliente = Cliente::factory()->create();

        $payload = [
            'customer_id'     => $cliente->id,
            'channel'         => 'whatsapp',
            'direction'       => 'outbound',
            'body'            => 'Messaggio inviato.',
            'notification_id' => 'notif-abc-123',
        ];

        $this->service->logAutomatic($payload);
        $result = $this->service->logAutomatic($payload); // secondo invio

        $this->assertNull($result);
        $this->assertDatabaseCount('communications', 1);
    }
}
