<?php

namespace App\Actions\Magazzino;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

            return MovimentoMagazzino::create([
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
        });
    }
}
