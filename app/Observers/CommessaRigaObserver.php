<?php

namespace App\Observers;

use App\Models\CommessaRiga;

class CommessaRigaObserver
{
    public function saved(CommessaRiga $riga): void
    {
        $this->aggiornaflag($riga);
    }

    public function deleted(CommessaRiga $riga): void
    {
        $this->aggiornaFlag($riga);
    }

    private function aggiornaFlag(CommessaRiga $riga): void
    {
        $commessa = $riga->commessa;
        if (! $commessa) return;

        $haGaranzia = $commessa->righe()->where('in_garanzia', true)->exists();
        $commessa->updateQuietly(['ha_righe_garanzia' => $haGaranzia]);
    }
}
