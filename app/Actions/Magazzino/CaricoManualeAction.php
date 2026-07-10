<?php

namespace App\Actions\Magazzino;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class CaricoManualeAction
{
    /**
     * Esegue un carico, un reso fornitore o un reso cliente.
     * Aggiorna la giacenza e crea il movimento in un'unica transaction.
     */
    public function execute(
        Articolo $articolo,
        TipoMovimento $tipo,
        int $quantita,
        User $utente,
        ?float $prezzoUnitario = null,
        ?string $documentoFornitore = null,
        ?\DateTimeInterface $dataDocumento = null,
        ?string $note = null,
    ): MovimentoMagazzino {
        return DB::transaction(function () use ($articolo, $tipo, $quantita, $utente, $prezzoUnitario, $documentoFornitore, $dataDocumento, $note) {
            $articolo = Articolo::lockForUpdate()->find($articolo->id);

            $giacenzaPrecedente = $articolo->giacenza_attuale;

            $giacenzaSuccessiva = $tipo->aumentaGiacenza()
                ? $giacenzaPrecedente + $quantita
                : $giacenzaPrecedente - $quantita;

            $articolo->update(['giacenza_attuale' => $giacenzaSuccessiva]);

            $movimento = MovimentoMagazzino::create([
                'articolo_id'        => $articolo->id,
                'tipo'               => $tipo,
                'quantita'           => $quantita,
                'giacenza_precedente' => $giacenzaPrecedente,
                'giacenza_successiva' => $giacenzaSuccessiva,
                'prezzo_unitario'    => $prezzoUnitario,
                'documento_fornitore' => $documentoFornitore,
                'data_documento'     => $dataDocumento,
                'user_id'            => $utente->id,
                'note'               => $note,
                'created_at'         => now(),
            ]);

            // Movimenti di storno non sono annullabili
            if (! str_starts_with((string) $note, 'Storno movimento #')) {
                $activity = Activity::where('subject_type', MovimentoMagazzino::class)
                    ->where('subject_id', $movimento->id)
                    ->latest('id')
                    ->first();

                if ($activity && ! isset($activity->properties['undoable'])) {
                    $activity->properties = $activity->properties->merge(['undoable' => true]);
                    $activity->save();
                }
            }

            return $movimento;
        });
    }
}
