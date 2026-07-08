<?php

namespace Tests\Feature;

use App\Contracts\LoggableCommunication;
use App\Listeners\LogOutboundNotification;
use App\Models\Cliente;
use App\Models\Communication;
use App\Models\User;
use App\Services\Communications\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class LogOutboundNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeListener(): LogOutboundNotification
    {
        return new LogOutboundNotification(app(CommunicationService::class));
    }

    private function makeEvent(Notification $notification): NotificationSent
    {
        $notifiable = User::factory()->make();
        return new NotificationSent($notifiable, $notification, 'database', []);
    }

    public function test_creates_communication_from_loggable_notification(): void
    {
        $cliente = Cliente::factory()->create();

        $notification = new class($cliente->id) extends Notification implements LoggableCommunication {
            public function __construct(private int $clienteId) {}
            public function via($notifiable): array { return ['database']; }
            public function communicationPayload(): array {
                return [
                    'customer_id'     => $this->clienteId,
                    'work_order_id'   => null,
                    'channel'         => 'sms',
                    'direction'       => 'outbound',
                    'subject'         => null,
                    'body'            => 'Promemoria appuntamento.',
                    'occurred_at'     => null,
                    'notification_id' => 'test-notif-001',
                ];
            }
        };

        $this->makeListener()->handle($this->makeEvent($notification));

        $this->assertDatabaseHas('communications', [
            'customer_id' => $cliente->id,
            'channel'     => 'sms',
            'direction'   => 'outbound',
        ]);
    }

    public function test_ignores_non_loggable_notification(): void
    {
        $notification = new class extends Notification {
            public function via($notifiable): array { return ['database']; }
        };

        $this->makeListener()->handle($this->makeEvent($notification));

        $this->assertDatabaseCount('communications', 0);
    }

    public function test_idempotent_on_duplicate_notification_id(): void
    {
        $cliente = Cliente::factory()->create();

        $makeNotification = fn() => new class($cliente->id) extends Notification implements LoggableCommunication {
            public function __construct(private int $clienteId) {}
            public function via($notifiable): array { return ['database']; }
            public function communicationPayload(): array {
                return [
                    'customer_id'     => $this->clienteId,
                    'work_order_id'   => null,
                    'channel'         => 'whatsapp',
                    'direction'       => 'outbound',
                    'subject'         => null,
                    'body'            => 'Messaggio duplicato.',
                    'occurred_at'     => null,
                    'notification_id' => 'dup-notif-999',
                ];
            }
        };

        $listener = $this->makeListener();
        $listener->handle($this->makeEvent($makeNotification()));
        $listener->handle($this->makeEvent($makeNotification()));

        $this->assertDatabaseCount('communications', 1);
    }
}
