<?php

namespace App\Observers;

use App\Enums\TipoRiga;
use App\Models\CommessaRiga;
use App\Services\Pricing\MatricePrezzoService;

class CommessaRigaObserver
{
    public function creating(CommessaRiga $riga): void
    {
        if ($riga->tipo === TipoRiga::Articolo && $riga->articolo_id !== null) {
            // Copia prezzo_acquisto dall'articolo se non specificato
            if ($riga->prezzo_acquisto === null || (float) $riga->prezzo_acquisto === 0.0) {
                $riga->prezzo_acquisto = $riga->articolo?->prezzo_acquisto
                    ?? optional(\App\Models\Articolo::find($riga->articolo_id))->prezzo_acquisto;
            }

            // Auto-suggerisci prezzo_unitario dalla matrice default se non specificato
            if ((float) $riga->prezzo_unitario === 0.0 && $riga->prezzo_acquisto !== null) {
                $suggerito = app(MatricePrezzoService::class)->suggestPrice($riga->prezzo_acquisto);
                if ($suggerito !== null) {
                    $riga->prezzo_unitario = $suggerito;
                }
            }
        }
    }

    public function saved(CommessaRiga $riga): void
    {
        $this->aggiornaFlag($riga);
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
