<?php

namespace App\Actions\Lavorazione;

use App\Models\Lavorazione;

class FermaLavorazioneAction
{
    public function execute(Lavorazione $lavorazione): void
    {
        if (! $lavorazione->started_at || $lavorazione->stopped_at) {
            return;
        }

        $stoppedAt = now();
        $minuti = (int) ceil($lavorazione->started_at->diffInMinutes($stoppedAt));

        $lavorazione->update([
            'stopped_at'       => $stoppedAt,
            'minuti_effettivi' => $minuti,
        ]);
    }
}
