<?php

namespace App\Services\Undo;

use App\Contracts\UndoHandler;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class UndoService
{
    /** @var UndoHandler[] */
    private array $handlers;

    public function __construct()
    {
        $this->handlers = array_map(
            fn (string $class) => app($class),
            config('undo.handlers', [])
        );
    }

    public function canUndo(Activity $activity, User $user): bool
    {
        if (! ($activity->properties['undoable'] ?? false)) {
            return false;
        }

        if (isset($activity->properties['undone_at'])) {
            return false;
        }

        $window = (int) config('undo.window_minutes', 10);
        if ($activity->created_at->lt(now()->subMinutes($window))) {
            return false;
        }

        $canByAny = $user->can('undo-any') || $user->hasRole('admin');
        if (! $canByAny && $activity->causer_id !== $user->id) {
            return false;
        }

        return $this->findHandler($activity) !== null;
    }

    public function undo(Activity $activity, User $user): void
    {
        DB::transaction(function () use ($activity, $user) {
            /** @var Activity $locked */
            $locked = Activity::lockForUpdate()->find($activity->id);

            if (! $locked || isset($locked->properties['undone_at'])) {
                throw new RuntimeException('Operazione già annullata o non trovata.');
            }

            $handler = $this->findHandler($locked);
            if (! $handler) {
                throw new RuntimeException('Nessun handler disponibile per questa operazione.');
            }

            if (! $this->canUndo($locked, $user)) {
                throw new RuntimeException('Annullamento non consentito.');
            }

            $compensazione = $handler->undo($locked, $user);

            $locked->properties = $locked->properties->merge([
                'undone_at'            => now()->toIso8601String(),
                'undone_by'            => $user->id,
                'compensazione_id'     => $compensazione->id,
            ]);
            $locked->save();
        });
    }

    private function findHandler(Activity $activity): ?UndoHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($activity)) {
                return $handler;
            }
        }
        return null;
    }
}
