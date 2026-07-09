<?php

namespace App\Actions\Parts;

use App\Models\Articolo;
use App\Models\User;

class BulkUpdateLocationAction
{
    /** Returns count of updated records */
    public function execute(array $ids, string $nuovaUbicazione, User $utente): int
    {
        $aggiornati = 0;

        foreach (array_chunk($ids, 50) as $chunk) {
            foreach (Articolo::whereIn('id', $chunk)->get() as $art) {
                $old = $art->ubicazione;
                $art->update(['ubicazione' => $nuovaUbicazione]);

                activity()
                    ->causedBy($utente)
                    ->performedOn($art)
                    ->withProperties(['old' => ['ubicazione' => $old], 'new' => ['ubicazione' => $nuovaUbicazione]])
                    ->log('ubicazione_aggiornata_bulk');

                $aggiornati++;
            }
        }

        return $aggiornati;
    }
}
