<?php

namespace App\Actions\Lavorazione;

use App\Models\Lavorazione;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AvviaLavorazioneAction
{
    /**
     * Avvia il timer di una lavorazione.
     * Blocca se il meccanico ha già una lavorazione attiva.
     */
    public function execute(Lavorazione $lavorazione, User $meccanico): void
    {
        $attive = Lavorazione::attive()->where('user_id', $meccanico->id)->count();

        if ($attive > 0) {
            throw ValidationException::withMessages([
                'lavorazione' => 'Hai già una lavorazione in corso. Fermala prima di avviarne un\'altra.',
            ]);
        }

        $lavorazione->update([
            'started_at'  => now(),
            'stopped_at'  => null,
            'minuti_effettivi' => null,
        ]);
    }
}
