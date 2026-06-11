<?php

namespace App\Actions\Acquisti;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Enums\StatoOrdineFornitore;
use App\Enums\TipoMovimento;
use App\Models\DdtFornitore;
use App\Models\OrdineFornitore;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RicezioneMerceAction
{
    public function __construct(private CaricoManualeAction $caricoAction) {}

    /**
     * Registra la ricezione merce, crea DDT, aggiorna quantità ordine, carica magazzino.
     *
     * @param  array  $righe  [['ordine_riga_id', 'quantita_ricevuta', 'prezzo_unitario'?], ...]
     */
    public function execute(
        OrdineFornitore $ordine,
        string $numeroDdt,
        string $dataDdt,
        string $dataRicezione,
        array $righe,
        User $utente,
        ?string $note = null,
    ): DdtFornitore {
        return DB::transaction(function () use ($ordine, $numeroDdt, $dataDdt, $dataRicezione, $righe, $utente, $note) {

            $ddt = DdtFornitore::create([
                'ordine_fornitore_id' => $ordine->id,
                'fornitore_id'        => $ordine->fornitore_id,
                'numero_ddt'          => $numeroDdt,
                'data_ddt'            => $dataDdt,
                'data_ricezione'      => $dataRicezione,
                'note'                => $note,
                'user_id'             => $utente->id,
            ]);

            $ordine->load('righe');

            foreach ($righe as $rigaDati) {
                $ordineRiga = $ordine->righe->firstWhere('id', $rigaDati['ordine_riga_id']);
                if (! $ordineRiga) {
                    continue;
                }

                $qta = (int) ($rigaDati['quantita_ricevuta'] ?? 0);
                if ($qta <= 0) {
                    continue;
                }

                $prezzo = isset($rigaDati['prezzo_unitario']) ? (float) $rigaDati['prezzo_unitario'] : null;

                // Crea riga DDT
                $ddt->righe()->create([
                    'ordine_riga_id'   => $ordineRiga->id,
                    'articolo_id'      => $ordineRiga->articolo_id,
                    'descrizione'      => $ordineRiga->descrizione,
                    'quantita_ricevuta' => $qta,
                    'prezzo_unitario'  => $prezzo,
                ]);

                // Aggiorna quantità ricevuta sull'ordine
                $ordineRiga->increment('quantita_ricevuta', $qta);

                // Carico magazzino se la riga è collegata a un articolo
                if ($ordineRiga->articolo_id) {
                    $this->caricoAction->execute(
                        articolo: $ordineRiga->articolo,
                        tipo: TipoMovimento::Carico,
                        quantita: $qta,
                        utente: $utente,
                        prezzoUnitario: $prezzo ?? (float) $ordineRiga->prezzo_unitario_atteso,
                        documentoFornitore: $numeroDdt,
                        dataDocumento: new \DateTime($dataDdt),
                        note: "Ricezione ordine {$ordine->numero}",
                    );
                }
            }

            // Aggiorna stato ordine
            $ordine->load('righe');
            $nuovoStato = $ordine->isCompletamenteRicevuto()
                ? StatoOrdineFornitore::Ricevuto
                : StatoOrdineFornitore::ParzialmenteRicevuto;

            $ordine->update(['stato' => $nuovoStato]);

            return $ddt;
        });
    }
}
