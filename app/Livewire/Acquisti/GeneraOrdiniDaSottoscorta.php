<?php

namespace App\Livewire\Acquisti;

use App\Enums\StatoOrdineFornitore;
use App\Models\Articolo;
use App\Models\OrdineFornitore;
use App\Models\OrdineFornitoreRiga;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GeneraOrdiniDaSottoscorta extends Component
{
    /** Selezioni: [fornitore_id => [articolo_id => ['selezionato' => bool, 'quantita' => int]]] */
    public array $selezioni = [];

    public bool $generato = false;
    public array $ordiniGenerati = [];

    public function mount(): void
    {
        $this->caricaSottoscorta();
    }

    private function caricaSottoscorta(): void
    {
        $articoli = Articolo::with('fornitore')
            ->attivi()
            ->sottoScorta()
            ->whereNotNull('fornitore_id')
            ->get();

        $this->selezioni = [];

        foreach ($articoli as $art) {
            $fid = $art->fornitore_id;
            if (! isset($this->selezioni[$fid])) {
                $this->selezioni[$fid] = [];
            }

            $qtaOrdinare = max(1, (int) $art->scorta_massima - (int) $art->giacenza_attuale);

            $this->selezioni[$fid][$art->id] = [
                'selezionato' => true,
                'quantita'    => $qtaOrdinare,
                'descrizione' => $art->descrizione,
                'codice'      => $art->codice,
                'codice_fornitore' => $art->codice_fornitore,
                'giacenza'    => $art->giacenza_attuale,
                'scorta_min'  => $art->scorta_minima,
                'scorta_max'  => $art->scorta_massima,
                'prezzo_acq'  => $art->prezzo_acquisto,
            ];
        }
    }

    public function generaOrdini(): void
    {

        $numerazione  = app(NumerazioneService::class);
        $anno         = now()->year;
        $ordiniCreati = [];

        DB::transaction(function () use ($numerazione, $anno, &$ordiniCreati) {
            foreach ($this->selezioni as $fornitoreId => $articoli) {
                $righeSelezionate = array_filter($articoli, fn($a) => $a['selezionato'] && $a['quantita'] > 0);

                if (empty($righeSelezionate)) {
                    continue;
                }

                $progressivo = $numerazione->prossimoOrdineFornitore($anno);
                $numero      = "ORD-{$anno}-" . str_pad($progressivo, 4, '0', STR_PAD_LEFT);

                $ordine = OrdineFornitore::create([
                    'numero'       => $numero,
                    'anno'         => $anno,
                    'progressivo'  => $progressivo,
                    'fornitore_id' => $fornitoreId,
                    'stato'        => StatoOrdineFornitore::Bozza,
                    'data_ordine'  => today(),
                    'user_id'      => auth()->id(),
                ]);

                foreach ($righeSelezionate as $articoloId => $dati) {
                    OrdineFornitoreRiga::create([
                        'ordine_fornitore_id'    => $ordine->id,
                        'articolo_id'            => $articoloId,
                        'descrizione'            => $dati['descrizione'],
                        'codice_fornitore'       => $dati['codice_fornitore'] ?: null,
                        'quantita_ordinata'      => (int) $dati['quantita'],
                        'prezzo_unitario_atteso' => $dati['prezzo_acq'] ?: null,
                    ]);
                }

                $ordiniCreati[] = $ordine->id;
            }
        });

        $this->ordiniGenerati = $ordiniCreati;
        $this->generato       = true;
        session()->flash('success', count($ordiniCreati) . ' ordini creati con successo.');
    }

    public function render()
    {
        $fornitoriConArticoli = [];

        foreach ($this->selezioni as $fornitoreId => $articoli) {
            $fornitore = \App\Models\Fornitore::find($fornitoreId);
            if ($fornitore) {
                $fornitoriConArticoli[] = [
                    'fornitore' => $fornitore,
                    'articoli'  => $articoli,
                    'fid'       => $fornitoreId,
                ];
            }
        }

        return view('livewire.acquisti.genera-ordini-da-sottoscorta', [
            'fornitoriConArticoli' => $fornitoriConArticoli,
        ]);
    }
}
