<?php

namespace App\Livewire\Acquisti;

use App\Enums\StatoOrdineFornitore;
use App\Models\Articolo;
use App\Models\Fornitore;
use App\Models\OrdineFornitore;
use App\Models\OrdineFornitoreRiga;
use App\Services\NumerazioneService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Rule;
use Livewire\Component;

class DettaglioOrdineFornitore extends Component
{
    public ?OrdineFornitore $ordine = null;
    public bool $nuovoOrdine = false;

    // Form ordine
    #[Rule('required|exists:fornitori,id')]
    public int $fornitoreId = 0;

    #[Rule('nullable|date')]
    public string $dataConsegnaPrevista = '';

    #[Rule('nullable|string|max:1000')]
    public string $note = '';

    // Righe inline
    public array $righe = [];

    // Ricerca articolo
    public string $ricercaArticolo = '';
    public array $articoliSuggeriti = [];

    public function mount(int|string|null $ordineId = null): void
    {
        if ($ordineId) {
            $this->ordine = OrdineFornitore::with(['fornitore', 'righe.articolo'])->findOrFail($ordineId);
            $this->fornitoreId          = $this->ordine->fornitore_id;
            $this->dataConsegnaPrevista = $this->ordine->data_consegna_prevista?->toDateString() ?? '';
            $this->note                 = $this->ordine->note ?? '';

            $this->righe = $this->ordine->righe->map(fn($r) => [
                'id'                    => $r->id,
                'articolo_id'           => $r->articolo_id,
                'descrizione'           => $r->descrizione,
                'codice_fornitore'      => $r->codice_fornitore ?? '',
                'quantita_ordinata'     => $r->quantita_ordinata,
                'quantita_ricevuta'     => $r->quantita_ricevuta,
                'prezzo_unitario_atteso' => $r->prezzo_unitario_atteso,
            ])->toArray();
        } else {
            $this->nuovoOrdine = true;
            $this->aggiungiRiga();
        }
    }

    public function updatedRicercaArticolo(): void
    {
        if (strlen($this->ricercaArticolo) < 2) {
            $this->articoliSuggeriti = [];
            return;
        }

        $this->articoliSuggeriti = Articolo::attivi()
            ->where(function ($q) {
                $q->where('descrizione', 'like', "%{$this->ricercaArticolo}%")
                  ->orWhere('codice', 'like', "%{$this->ricercaArticolo}%")
                  ->orWhere('codice_fornitore', 'like', "%{$this->ricercaArticolo}%");
            })
            ->limit(8)
            ->get(['id', 'codice', 'descrizione', 'codice_fornitore', 'prezzo_acquisto'])
            ->toArray();
    }

    public function selezionaArticolo(int $articoloId): void
    {
        $art = Articolo::find($articoloId);
        if (! $art) return;

        $this->aggiungiRiga([
            'articolo_id'            => $art->id,
            'descrizione'            => $art->descrizione,
            'codice_fornitore'       => $art->codice_fornitore ?? '',
            'quantita_ordinata'      => 1,
            'prezzo_unitario_atteso' => $art->prezzo_acquisto,
        ]);

        $this->ricercaArticolo   = '';
        $this->articoliSuggeriti = [];
    }

    public function aggiungiRiga(array $dati = []): void
    {
        $this->righe[] = array_merge([
            'id'                    => null,
            'articolo_id'           => null,
            'descrizione'           => '',
            'codice_fornitore'      => '',
            'quantita_ordinata'     => 1,
            'quantita_ricevuta'     => 0,
            'prezzo_unitario_atteso' => null,
        ], $dati);
    }

    public function rimuoviRiga(int $index): void
    {
        array_splice($this->righe, $index, 1);
    }

    public function salva(): void
    {
        $this->validate();

        DB::transaction(function () {
            if ($this->nuovoOrdine) {
                $numerazione = app(NumerazioneService::class);
                $anno        = now()->year;
                $progressivo = $numerazione->prossimoOrdineFornitore($anno);
                $numero      = "ORD-{$anno}-" . str_pad($progressivo, 4, '0', STR_PAD_LEFT);

                $this->ordine = OrdineFornitore::create([
                    'numero'                 => $numero,
                    'anno'                   => $anno,
                    'progressivo'            => $progressivo,
                    'fornitore_id'           => $this->fornitoreId,
                    'stato'                  => StatoOrdineFornitore::Bozza,
                    'data_ordine'            => today(),
                    'data_consegna_prevista' => $this->dataConsegnaPrevista ?: null,
                    'note'                   => $this->note ?: null,
                    'user_id'                => auth()->id(),
                ]);

                $this->nuovoOrdine = false;
            } else {
                $this->ordine->update([
                    'fornitore_id'           => $this->fornitoreId,
                    'data_consegna_prevista' => $this->dataConsegnaPrevista ?: null,
                    'note'                   => $this->note ?: null,
                ]);

                OrdineFornitoreRiga::where('ordine_fornitore_id', $this->ordine->id)->delete();
            }

            foreach ($this->righe as $riga) {
                if (! $riga['descrizione']) continue;

                OrdineFornitoreRiga::create([
                    'ordine_fornitore_id'    => $this->ordine->id,
                    'articolo_id'            => $riga['articolo_id'] ?: null,
                    'descrizione'            => $riga['descrizione'],
                    'codice_fornitore'       => $riga['codice_fornitore'] ?: null,
                    'quantita_ordinata'      => max(1, (int) $riga['quantita_ordinata']),
                    'prezzo_unitario_atteso' => $riga['prezzo_unitario_atteso'] ?: null,
                ]);
            }
        });

        $this->ordine->load('righe.articolo');
        $this->righe = $this->ordine->righe->map(fn($r) => [
            'id'                    => $r->id,
            'articolo_id'           => $r->articolo_id,
            'descrizione'           => $r->descrizione,
            'codice_fornitore'      => $r->codice_fornitore ?? '',
            'quantita_ordinata'     => $r->quantita_ordinata,
            'quantita_ricevuta'     => $r->quantita_ricevuta,
            'prezzo_unitario_atteso' => $r->prezzo_unitario_atteso,
        ])->toArray();

        session()->flash('success', 'Ordine salvato.');
    }

    public function segnaInviato(): void
    {
        $this->ordine->update(['stato' => StatoOrdineFornitore::Inviato]);
        session()->flash('success', 'Ordine segnato come inviato.');
    }

    public function segnaConfermato(): void
    {
        $this->validate(['dataConsegnaPrevista' => 'required|date']);
        $this->ordine->update([
            'stato'                  => StatoOrdineFornitore::Confermato,
            'data_consegna_prevista' => $this->dataConsegnaPrevista,
        ]);
        session()->flash('success', 'Ordine confermato.');
    }

    public function stampaPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $ordine = OrdineFornitore::with(['fornitore', 'righe.articolo'])->findOrFail($this->ordine->id);
        $pdf    = Pdf::loadView('pdf.ordine-fornitore', compact('ordine'))->setPaper('a4', 'portrait');
        return $pdf->download("ordine-{$ordine->numero}.pdf");
    }

    public function render()
    {
        return view('livewire.acquisti.dettaglio-ordine-fornitore', [
            'fornitori' => Fornitore::orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
        ]);
    }
}
