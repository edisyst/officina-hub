<?php

namespace App\Actions\Magazzino;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RettificaInventarioAction
{
    /**
     * Porta la giacenza al valore indicato creando un movimento di rettifica.
     * La quantità nel movimento è la differenza assoluta.
     */
    public function execute(
        Articolo $articolo,
        int $nuovaGiacenza,
        User $utente,
        string $nota,
    ): MovimentoMagazzino {
        return DB::transaction(function () use ($articolo, $nuovaGiacenza, $utente, $nota) {
            $articolo->lockForUpdate()->find($articolo->id);
            $articolo->refresh();

            $giacenzaPrecedente = $articolo->giacenza_attuale;
            $differenza = abs($nuovaGiacenza - $giacenzaPrecedente);

            $articolo->update(['giacenza_attuale' => $nuovaGiacenza]);

            return MovimentoMagazzino::create([
                'articolo_id'         => $articolo->id,
                'tipo'                => TipoMovimento::Rettifica,
                'quantita'            => $differenza,
                'giacenza_precedente' => $giacenzaPrecedente,
                'giacenza_successiva' => $nuovaGiacenza,
                'user_id'             => $utente->id,
                'note'                => $nota,
                'created_at'          => now(),
            ]);
        });
    }
}
