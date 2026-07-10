<?php

namespace App\Services\Undo\Handlers;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Contracts\UndoHandler;
use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Models\User;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class WorkOrderStatusUndoHandler implements UndoHandler
{
    public function supports(Activity $activity): bool
    {
        return $activity->subject_type === Commessa::class
            && $activity->event === 'updated'
            && isset($activity->properties['old']['stato']);
    }

    public function undo(Activity $activity, User $user): Activity
    {
        /** @var Commessa $commessa */
        $commessa = Commessa::findOrFail($activity->subject_id);

        $statoPrecedente = StatoCommessa::from($activity->properties['old']['stato']);

        if (! $commessa->puoTransireA($statoPrecedente)) {
            throw new RuntimeException(
                "Transizione inversa non ammessa: da '{$commessa->stato->label()}' a '{$statoPrecedente->label()}'."
            );
        }

        app(AggiornaStatoAction::class)->execute($commessa, $statoPrecedente, $user, 'Annullamento automatico');

        $act = Activity::where('subject_type', Commessa::class)
            ->where('subject_id', $commessa->id)
            ->latest('id')
            ->firstOrFail();

        return $act;
    }
}
