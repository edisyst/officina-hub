<?php

namespace App\Actions\Magazzino;

use App\Enums\TipoMovimento;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\Commessa;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScaricoCommessaAction
{
    /**
     * Scarica automaticamente dal magazzino tutte le righe articolo
     * di una commessa al passaggio allo stato "completata".
     */
    public function execute(Commessa $commessa, User $utente): void
    {
        $righe = $commessa->righe()
            ->where('tipo', TipoRiga::Articolo->value)
            ->whereNotNull('articolo_id')
            ->get();

        foreach ($righe as $riga) {
            DB::transaction(function () use ($commessa, $riga, $utente) {
                /** @var Articolo $articolo */
                $articolo = Articolo::lockForUpdate()->find($riga->articolo_id);

                if (! $articolo) {
                    return;
                }

                $quantita = (int) ceil((float) $riga->quantita);
                $giacenzaPrecedente = $articolo->giacenza_attuale;
                $giacenzaSuccessiva = $giacenzaPrecedente - $quantita;

                $nota = null;
                if ($giacenzaSuccessiva < 0) {
                    $nota = 'SCARICO IN NEGATIVO — giacenza insufficiente';
                    Log::warning("Magazzino: scarico in negativo per articolo #{$articolo->id} ({$articolo->codice}) su commessa #{$commessa->id}");
                }

                $articolo->update(['giacenza_attuale' => $giacenzaSuccessiva]);

                MovimentoMagazzino::create([
                    'articolo_id'         => $articolo->id,
                    'tipo'                => TipoMovimento::Scarico,
                    'quantita'            => $quantita,
                    'giacenza_precedente' => $giacenzaPrecedente,
                    'giacenza_successiva' => $giacenzaSuccessiva,
                    'prezzo_unitario'     => (float) $riga->prezzo_acquisto ?: null,
                    'commessa_id'         => $commessa->id,
                    'user_id'             => $utente->id,
                    'note'                => $nota,
                    'created_at'          => now(),
                ]);
            });
        }
    }
}
