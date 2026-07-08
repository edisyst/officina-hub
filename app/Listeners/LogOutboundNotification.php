<?php

namespace App\Listeners;

use App\Contracts\LoggableCommunication;
use App\Services\Communications\CommunicationService;
use Illuminate\Notifications\Events\NotificationSent;

class LogOutboundNotification
{
    public function __construct(private readonly CommunicationService $service) {}

    public function handle(NotificationSent $event): void
    {
        if (! ($event->notification instanceof LoggableCommunication)) {
            return;
        }

        $payload = $event->notification->communicationPayload();

        if (empty($payload['customer_id'])) {
            return;
        }

        $this->service->logAutomatic($payload);
    }
}
