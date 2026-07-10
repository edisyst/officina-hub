<?php

namespace App\Services\Activity;

use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityFeedService
{
    public function humanize(Activity $activity): string
    {
        $autore = $activity->causer instanceof User
            ? $activity->causer->name
            : 'Sistema';

        return match(true) {
            $this->isStockMovement($activity)   => $this->humanizeStock($activity, $autore),
            $this->isCommessaStatus($activity)  => $this->humanizeStatus($activity, $autore),
            $this->isRigaCreated($activity)     => $this->humanizeRiga($activity, $autore),
            $this->isRigaAnnullata($activity)   => $this->humanizeRigaAnnullata($activity, $autore),
            default                             => $this->fallback($activity, $autore),
        };
    }

    private function isStockMovement(Activity $activity): bool
    {
        return $activity->subject_type === MovimentoMagazzino::class;
    }

    private function isCommessaStatus(Activity $activity): bool
    {
        return $activity->subject_type === Commessa::class
            && $activity->event === 'updated'
            && isset($activity->properties['attributes']['stato']);
    }

    private function isRigaCreated(Activity $activity): bool
    {
        return $activity->log_name === 'commessa_riga'
            && $activity->event === 'created';
    }

    private function isRigaAnnullata(Activity $activity): bool
    {
        return $activity->log_name === 'commessa_riga'
            && $activity->event === 'compensazione';
    }

    private function humanizeStock(Activity $activity, string $autore): string
    {
        /** @var MovimentoMagazzino|null $movimento */
        $movimento = $activity->subject;

        if (! $movimento) {
            return "{$autore} ha registrato un movimento magazzino.";
        }

        $movimento->loadMissing('articolo', 'commessa');

        $articoloDesc = $movimento->articolo?->descrizione ?? "articolo #{$movimento->articolo_id}";
        $tipoLabel    = $movimento->tipo->label();
        $quantita     = (int) $movimento->quantita;
        $commessaRef  = $movimento->commessa ? " su OdL {$movimento->commessa->numero}" : '';

        return "{$autore} ha registrato {$tipoLabel}: {$quantita}× {$articoloDesc}{$commessaRef}.";
    }

    private function humanizeStatus(Activity $activity, string $autore): string
    {
        /** @var Commessa|null $commessa */
        $commessa = $activity->subject;

        if (! $commessa) {
            return "{$autore} ha aggiornato lo stato di una commessa.";
        }

        $nuovoStato = $activity->properties['attributes']['stato'] ?? $commessa->stato->value;
        $vecchioStato = $activity->properties['old']['stato'] ?? null;

        try {
            $nuovoLabel   = \App\Enums\StatoCommessa::from($nuovoStato)->label();
            $vecchioLabel = $vecchioStato ? \App\Enums\StatoCommessa::from($vecchioStato)->label() : null;
        } catch (\ValueError) {
            $nuovoLabel   = $nuovoStato;
            $vecchioLabel = $vecchioStato;
        }

        $commessa->loadMissing('veicolo');
        $targaRef = $commessa->veicolo?->targa ? " ({$commessa->veicolo->targa})" : '';

        $da = $vecchioLabel ? " da {$vecchioLabel}" : '';

        return "{$autore} ha cambiato stato OdL {$commessa->numero}{$targaRef}{$da} → {$nuovoLabel}.";
    }

    private function humanizeRiga(Activity $activity, string $autore): string
    {
        $props = $activity->properties;
        $descrizione = $props['descrizione'] ?? 'ricambio';
        $commessaNum = $props['commessa_numero'] ?? null;
        $commessaRef = $commessaNum ? " su OdL {$commessaNum}" : '';

        return "{$autore} ha aggiunto {$descrizione}{$commessaRef}.";
    }

    private function humanizeRigaAnnullata(Activity $activity, string $autore): string
    {
        $props = $activity->properties;
        $descrizione = $props['descrizione'] ?? 'riga';
        return "{$autore} ha annullato l'aggiunta di {$descrizione}.";
    }

    private function fallback(Activity $activity, string $autore): string
    {
        $evento = $activity->event ?? $activity->description ?? 'operazione';
        $tipo   = class_basename($activity->subject_type ?? 'entità');
        return "{$autore} ha eseguito {$evento} su {$tipo}.";
    }
}
