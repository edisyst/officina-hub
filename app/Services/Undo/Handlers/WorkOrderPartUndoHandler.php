<?php

namespace App\Services\Undo\Handlers;

use App\Contracts\UndoHandler;
use App\Models\CommessaRiga;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class WorkOrderPartUndoHandler implements UndoHandler
{
    public function supports(Activity $activity): bool
    {
        return $activity->log_name === 'commessa_riga'
            && $activity->event === 'created'
            && $activity->subject_type === CommessaRiga::class;
    }

    public function undo(Activity $activity, User $user): Activity
    {
        /** @var CommessaRiga $riga */
        $riga = CommessaRiga::with('commessa')->findOrFail($activity->subject_id);

        $riga->delete();

        return activity('commessa_riga')
            ->causedBy($user)
            ->performedOn($riga->commessa)
            ->withProperties([
                'riga_id'     => $riga->id,
                'descrizione' => $riga->descrizione,
            ])
            ->event('compensazione')
            ->log('riga_annullata');
    }
}
