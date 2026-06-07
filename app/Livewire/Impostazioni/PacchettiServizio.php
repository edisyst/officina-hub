<?php

namespace App\Livewire\Impostazioni;

use App\Models\Articolo;
use App\Models\PacchettoRiga;
use App\Models\PacchettoServizio;
use App\Models\TariffaManodopera;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PacchettiServizio extends Component
{
    use WithPagination;

    public string $cerca = '';
    public string $filtroTipoCommessa = '';
    public string $filtroTipoVeicolo = '';
    public string $filtroAlimentazione = '';

    public bool $showModal = false;
    public ?int $pacchettoId = null;

    // Header pacchetto
    #[Rule('required|string|max:255')]
    public string $nome = '';

    public string $descrizione = '';

    #[Rule('required|in:meccanica,carrozzeria,tagliando,entrambi')]
    public string $tipo_commessa = 'entrambi';

    #[Rule('required|in:auto,moto,entrambi')]
    public string $tipo_veicolo = 'entrambi';

    #[Rule('required|in:benzina,diesel,ibrido,elettrico,gpl,metano,tutte')]
    public string $alimentazione = 'tutte';

    public string $note = '';

    // Righe del pacchetto in editing (array di array)
    public array $righe = [];

    // Typeahead tariffa (per aggiungere riga manodopera)
    public string $nuovoTipo = 'manodopera';
    public string $cercaTariffa = '';
    public array $suggerimentiTariffe = [];
    public ?int $tariffaSelezionataId = null;

    // Typeahead articolo (per aggiungere riga articolo)
    public string $cercaArticolo = '';
    public array $suggerimentiArticoli = [];
    public ?int $articoloSelezionatoId = null;

    // Nuova riga generica
    public string $nuovaDescrizione = '';
    public float $nuovaQuantita = 1;
    public float $nuovaPrezzo = 0;
    public float $nuovaSconto = 0;
    public float $nuovaIva = 22;

    public function updatedCerca(): void
    {
        $this->resetPage();
    }

    public function apriNuovo(): void
    {
        $this->reset(['pacchettoId', 'nome', 'descrizione', 'note', 'righe']);
        $this->tipo_commessa = 'entrambi';
        $this->tipo_veicolo  = 'entrambi';
        $this->alimentazione = 'tutte';
        $this->resetAddRiga();
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $pacchetto = PacchettoServizio::with('righe.articolo')->findOrFail($id);

        $this->pacchettoId   = $pacchetto->id;
        $this->nome          = $pacchetto->nome;
        $this->descrizione   = $pacchetto->descrizione ?? '';
        $this->tipo_commessa = $pacchetto->tipo_commessa;
        $this->tipo_veicolo  = $pacchetto->tipo_veicolo;
        $this->alimentazione = $pacchetto->alimentazione;
        $this->note          = $pacchetto->note ?? '';

        $this->righe = $pacchetto->righe->map(fn($r) => [
            'id'                => $r->id,
            'tipo'              => $r->tipo,
            'descrizione'       => $r->descrizione,
            'articolo_id'       => $r->articolo_id,
            'articolo_label'    => $r->articolo ? $r->articolo->codice . ' — ' . $r->articolo->descrizione : null,
            'quantita'          => (float) $r->quantita,
            'prezzo_unitario'   => (float) $r->prezzo_unitario,
            'sconto_percentuale'=> (float) $r->sconto_percentuale,
            'iva_percentuale'   => (float) $r->iva_percentuale,
        ])->toArray();

        $this->resetAddRiga();
        $this->showModal = true;
    }

    private function resetAddRiga(): void
    {
        $this->nuovoTipo              = 'manodopera';
        $this->cercaTariffa           = '';
        $this->suggerimentiTariffe    = [];
        $this->tariffaSelezionataId   = null;
        $this->cercaArticolo          = '';
        $this->suggerimentiArticoli   = [];
        $this->articoloSelezionatoId  = null;
        $this->nuovaDescrizione       = '';
        $this->nuovaQuantita          = 1;
        $this->nuovaPrezzo            = 0;
        $this->nuovaSconto            = 0;
        $this->nuovaIva               = 22;
    }

    public function updatedCercaTariffa(): void
    {
        if (strlen($this->cercaTariffa) < 2) {
            $this->suggerimentiTariffe = [];
            return;
        }

        $this->suggerimentiTariffe = TariffaManodopera::attivi()
            ->search($this->cercaTariffa)
            ->limit(8)
            ->get(['id', 'codice', 'descrizione', 'categoria', 'minuti_standard', 'prezzo_listino', 'iva_percentuale'])
            ->toArray();
    }

    public function selezionaTariffa(int $id): void
    {
        $tariffa = TariffaManodopera::findOrFail($id);
        $this->tariffaSelezionataId = $tariffa->id;
        $this->cercaTariffa         = $tariffa->codice . ' — ' . $tariffa->descrizione;
        $this->nuovaDescrizione     = $tariffa->descrizione;
        $this->nuovaQuantita        = round($tariffa->minuti_standard / 60, 2);
        $this->nuovaPrezzo          = (float) $tariffa->prezzo_listino;
        $this->nuovaIva             = (float) $tariffa->iva_percentuale;
        $this->suggerimentiTariffe  = [];
    }

    public function updatedCercaArticolo(): void
    {
        if (strlen($this->cercaArticolo) < 2) {
            $this->suggerimentiArticoli = [];
            return;
        }

        $this->suggerimentiArticoli = Articolo::attivi()
            ->search($this->cercaArticolo)
            ->limit(8)
            ->get(['id', 'codice', 'descrizione', 'prezzo_vendita', 'iva_percentuale', 'giacenza_attuale'])
            ->toArray();
    }

    public function selezionaArticolo(int $id): void
    {
        $articolo = Articolo::findOrFail($id);
        $this->articoloSelezionatoId = $articolo->id;
        $this->cercaArticolo         = $articolo->codice . ' — ' . $articolo->descrizione;
        $this->nuovaDescrizione      = $articolo->descrizione;
        $this->nuovaPrezzo           = (float) $articolo->prezzo_vendita;
        $this->nuovaIva              = (float) $articolo->iva_percentuale;
        $this->nuovaQuantita         = 1;
        $this->suggerimentiArticoli  = [];
    }

    public function aggiungiRiga(): void
    {
        if ($this->nuovoTipo === 'nota') {
            if (empty(trim($this->nuovaDescrizione))) {
                $this->addError('nuovaDescrizione', 'Inserisci il testo della nota.');
                return;
            }
            $this->righe[] = [
                'id'                => null,
                'tipo'              => 'nota',
                'descrizione'       => $this->nuovaDescrizione,
                'articolo_id'       => null,
                'articolo_label'    => null,
                'quantita'          => 1,
                'prezzo_unitario'   => 0,
                'sconto_percentuale'=> 0,
                'iva_percentuale'   => 22,
            ];
        } elseif ($this->nuovoTipo === 'manodopera') {
            if (empty(trim($this->nuovaDescrizione))) {
                $this->addError('nuovaDescrizione', 'Inserisci la descrizione o seleziona una tariffa.');
                return;
            }
            $this->righe[] = [
                'id'                => null,
                'tipo'              => 'manodopera',
                'descrizione'       => $this->nuovaDescrizione,
                'articolo_id'       => null,
                'articolo_label'    => null,
                'quantita'          => $this->nuovaQuantita,
                'prezzo_unitario'   => $this->nuovaPrezzo,
                'sconto_percentuale'=> $this->nuovaSconto,
                'iva_percentuale'   => $this->nuovaIva,
            ];
        } elseif ($this->nuovoTipo === 'articolo') {
            if (empty(trim($this->nuovaDescrizione))) {
                $this->addError('nuovaDescrizione', 'Cerca e seleziona un articolo.');
                return;
            }
            $this->righe[] = [
                'id'                => null,
                'tipo'              => 'articolo',
                'descrizione'       => $this->nuovaDescrizione,
                'articolo_id'       => $this->articoloSelezionatoId,
                'articolo_label'    => $this->articoloSelezionatoId ? $this->cercaArticolo : null,
                'quantita'          => $this->nuovaQuantita,
                'prezzo_unitario'   => $this->nuovaPrezzo,
                'sconto_percentuale'=> $this->nuovaSconto,
                'iva_percentuale'   => $this->nuovaIva,
            ];
        }

        $this->resetAddRiga();
    }

    public function rimuoviRiga(int $index): void
    {
        array_splice($this->righe, $index, 1);
        $this->righe = array_values($this->righe);
    }

    public function aggiornaSortableRighe(array $order): void
    {
        $riordinate = [];
        foreach ($order as $oldIndex) {
            if (isset($this->righe[$oldIndex])) {
                $riordinate[] = $this->righe[$oldIndex];
            }
        }
        $this->righe = $riordinate;
    }

    public function salva(): void
    {
        $this->validate();

        $totale = collect($this->righe)
            ->where('tipo', '!=', 'nota')
            ->sum(function ($r) {
                $imp = (float) $r['quantita'] * (float) $r['prezzo_unitario'] * (1 - (float) $r['sconto_percentuale'] / 100);
                return $imp * (1 + (float) $r['iva_percentuale'] / 100);
            });

        $datiPacchetto = [
            'nome'                    => $this->nome,
            'descrizione'             => $this->descrizione ?: null,
            'tipo_commessa'           => $this->tipo_commessa,
            'tipo_veicolo'            => $this->tipo_veicolo,
            'alimentazione'           => $this->alimentazione,
            'note'                    => $this->note ?: null,
            'prezzo_totale_suggerito' => $totale > 0 ? $totale : null,
        ];

        if ($this->pacchettoId) {
            $pacchetto = PacchettoServizio::findOrFail($this->pacchettoId);
            $pacchetto->update($datiPacchetto);
            // Elimina e ricrea le righe (più semplice che fare diff)
            $pacchetto->righe()->delete();
            session()->flash('success', 'Pacchetto aggiornato.');
        } else {
            $maxOrd    = PacchettoServizio::max('ordinamento') ?? 0;
            $pacchetto = PacchettoServizio::create(array_merge($datiPacchetto, [
                'attivo'     => true,
                'ordinamento'=> $maxOrd + 1,
            ]));
            session()->flash('success', 'Pacchetto creato.');
        }

        foreach ($this->righe as $index => $r) {
            $pacchetto->righe()->create([
                'tipo'               => $r['tipo'],
                'descrizione'        => $r['descrizione'],
                'articolo_id'        => $r['tipo'] === 'articolo' ? ($r['articolo_id'] ?? null) : null,
                'quantita'           => $r['tipo'] === 'nota' ? 1 : $r['quantita'],
                'prezzo_unitario'    => $r['tipo'] === 'nota' ? 0 : $r['prezzo_unitario'],
                'sconto_percentuale' => $r['tipo'] === 'nota' ? 0 : $r['sconto_percentuale'],
                'iva_percentuale'    => $r['tipo'] === 'nota' ? 22 : $r['iva_percentuale'],
                'ordinamento'        => $index,
            ]);
        }

        $this->showModal = false;
    }

    public function clona(int $id): void
    {
        $originale = PacchettoServizio::with('righe')->findOrFail($id);

        $clone = $originale->replicate();
        $clone->nome       = $originale->nome . ' (copia)';
        $clone->utilizzi   = 0;
        $clone->ordinamento = (PacchettoServizio::max('ordinamento') ?? 0) + 1;
        $clone->push();

        foreach ($originale->righe as $riga) {
            $cloneRiga = $riga->replicate();
            $cloneRiga->pacchetto_servizio_id = $clone->id;
            $cloneRiga->save();
        }

        session()->flash('success', 'Pacchetto clonato: ' . $clone->nome);
    }

    public function toggleAttivo(int $id): void
    {
        $pacchetto = PacchettoServizio::findOrFail($id);
        $pacchetto->update(['attivo' => ! $pacchetto->attivo]);
    }

    public function elimina(int $id): void
    {
        PacchettoServizio::findOrFail($id)->delete();
        session()->flash('success', 'Pacchetto eliminato.');
    }

    public function render()
    {
        $pacchetti = PacchettoServizio::when($this->cerca, fn($q) => $q->search($this->cerca))
            ->when($this->filtroTipoCommessa, fn($q) => $q->where('tipo_commessa', $this->filtroTipoCommessa))
            ->when($this->filtroTipoVeicolo, fn($q) => $q->where('tipo_veicolo', $this->filtroTipoVeicolo))
            ->when($this->filtroAlimentazione, fn($q) => $q->where('alimentazione', $this->filtroAlimentazione))
            ->withCount('righe')
            ->orderBy('ordinamento')
            ->paginate(15);

        // Calcola totale in tempo reale per il modal
        $totaleRighe = collect($this->righe)
            ->where('tipo', '!=', 'nota')
            ->sum(function ($r) {
                $imp = (float) ($r['quantita'] ?? 0) * (float) ($r['prezzo_unitario'] ?? 0) * (1 - (float) ($r['sconto_percentuale'] ?? 0) / 100);
                return $imp * (1 + (float) ($r['iva_percentuale'] ?? 22) / 100);
            });

        return view('livewire.impostazioni.pacchetti-servizio', compact('pacchetti', 'totaleRighe'));
    }
}
