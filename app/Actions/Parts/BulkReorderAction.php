<?php

namespace App\Actions\Parts;

use App\Enums\StatoOrdineFornitore;
use App\Models\Articolo;
use App\Models\OrdineFornitore;
use App\Models\OrdineFornitoreRiga;
use App\Models\User;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;

class BulkReorderAction
{
    public function __construct(private NumerazioneService $numerazione) {}

    /**
     * Creates supplier order drafts grouped by preferred supplier (Step 15 path).
     *
     * @return array{ordini: int[], senza_fornitore: int[]}
     */
    public function execute(array $articoloIds, User $utente): array
    {
        $articoli = Articolo::whereIn('id', $articoloIds)->get();

        $conFornitore   = $articoli->filter(fn($a) => $a->fornitore_id !== null)->groupBy('fornitore_id');
        $senzaFornitore = $articoli->filter(fn($a) => $a->fornitore_id === null)->pluck('id')->toArray();

        $ordiniCreati = [];
        $anno         = now()->year;

        DB::transaction(function () use ($conFornitore, $anno, $utente, &$ordiniCreati) {
            foreach ($conFornitore as $fornitoreId => $arts) {
                $progressivo = $this->numerazione->prossimoOrdineFornitore($anno);
                $numero      = 'ORD-' . $anno . '-' . str_pad($progressivo, 4, '0', STR_PAD_LEFT);

                $ordine = OrdineFornitore::create([
                    'numero'       => $numero,
                    'anno'         => $anno,
                    'progressivo'  => $progressivo,
                    'fornitore_id' => $fornitoreId,
                    'stato'        => StatoOrdineFornitore::Bozza,
                    'data_ordine'  => today(),
                    'user_id'      => $utente->id,
                    'note'         => 'Generato da riordino massivo',
                ]);

                foreach ($arts as $art) {
                    $qtaOrdinare = max(1, (int) $art->scorta_massima - (int) $art->giacenza_attuale);

                    OrdineFornitoreRiga::create([
                        'ordine_fornitore_id'    => $ordine->id,
                        'articolo_id'            => $art->id,
                        'descrizione'            => $art->descrizione,
                        'codice_fornitore'       => $art->codice_fornitore,
                        'quantita_ordinata'      => $qtaOrdinare,
                        'prezzo_unitario_atteso' => $art->prezzo_acquisto ?: null,
                    ]);
                }

                $ordiniCreati[] = $ordine->id;
            }
        });

        return ['ordini' => $ordiniCreati, 'senza_fornitore' => $senzaFornitore];
    }

    /** CSV fallback: used when testing without Step 15 or for senza_fornitore items */
    public function toCsv(array $articoloIds): string
    {
        $articoli = Articolo::with('fornitore')->whereIn('id', $articoloIds)->orderBy('descrizione')->get();

        $rows = ["\xEF\xBB\xBF" . 'Codice;Descrizione;Fornitore;Giacenza;Scorta Minima;Da Ordinare'];

        foreach ($articoli as $art) {
            $daOrdinare = max(1, (int) $art->scorta_minima - (int) $art->giacenza_attuale);
            $rows[] = implode(';', [
                $art->codice,
                '"' . str_replace('"', '""', $art->descrizione) . '"',
                '"' . str_replace('"', '""', $art->fornitore?->ragione_sociale ?? '') . '"',
                $art->giacenza_attuale,
                $art->scorta_minima,
                $daOrdinare,
            ]);
        }

        return implode("\r\n", $rows);
    }
}
