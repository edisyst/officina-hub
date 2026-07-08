<?php

namespace App\Contracts;

interface LoggableCommunication
{
    /**
     * Return payload for automatic communication logging.
     *
     * @return array{
     *     customer_id: int,
     *     work_order_id: int|null,
     *     channel: string,
     *     direction: string,
     *     subject: string|null,
     *     body: string,
     *     occurred_at: \DateTimeInterface|null,
     *     notification_id: string|null,
     * }
     */
    public function communicationPayload(): array;
}
