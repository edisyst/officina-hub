<?php

namespace App\Services\Communications;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CommunicationService
{
    public function logManual(array $data, User $user): Communication
    {
        return Communication::create([
            'customer_id'    => $data['customer_id'],
            'work_order_id'  => $data['work_order_id'] ?? null,
            'user_id'        => $user->id,
            'channel'        => $data['channel'],
            'direction'      => $data['direction'],
            'subject'        => $data['subject'] ?? null,
            'body'           => $data['body'],
            'occurred_at'    => $data['occurred_at'] ?? now(),
            'meta'           => $data['meta'] ?? null,
        ]);
    }

    public function timelineFor(Cliente|Commessa $subject, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Communication::with(['user', 'workOrder'])
            ->orderByDesc('occurred_at');

        if ($subject instanceof Commessa) {
            $query->forWorkOrderContext($subject);
        } else {
            $query->where('customer_id', $subject->id);
        }

        if ($search) {
            if (\DB::connection()->getDriverName() === 'sqlite') {
                $query->where('body', 'like', "%{$search}%");
            } else {
                $query->whereFullText('body', $search);
            }
        }

        return $query->paginate($perPage);
    }

    public function logAutomatic(array $payload): ?Communication
    {
        $notificationId = $payload['notification_id'] ?? null;

        // Idempotency: skip if already logged
        if ($notificationId && Communication::where('meta->notification_id', $notificationId)->exists()) {
            return null;
        }

        return Communication::create([
            'customer_id'   => $payload['customer_id'],
            'work_order_id' => $payload['work_order_id'] ?? null,
            'user_id'       => null,
            'channel'       => $payload['channel'],
            'direction'     => $payload['direction'] ?? 'outbound',
            'subject'       => $payload['subject'] ?? null,
            'body'          => $payload['body'],
            'occurred_at'   => $payload['occurred_at'] ?? now(),
            'meta'          => $notificationId ? ['notification_id' => $notificationId] : null,
        ]);
    }
}
