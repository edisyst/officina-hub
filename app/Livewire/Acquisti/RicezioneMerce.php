<?php

namespace App\Livewire\Acquisti;

use App\Actions\Acquisti\RicezioneMerceAction;
use App\Models\OrdineFornitore;
use Livewire\Attributes\Rule;
use Livewire\Component;

class RicezioneMerce extends Component
{
    public OrdineFornitore $ordine;

    #[Rule('required|string|max:100')]
    public string $numeroDdt = '';

    #[Rule('required|date')]
    public string $dataDdt = '';

    #[Rule('required|date')]
    public string $dataRicezione = '';

    #[Rule('nullable|string|max:1000')]
    public string $note = '';

    public array $righe = [];

    public function mount(int $ordineId): void
    {
        $this->ordine        = OrdineFornitore::with(['fornitore', 'righe.articolo'])->findOrFail($ordineId);
        $this->dataDdt       = today()->toDateString();
        $this->dataRicezione = today()->toDateString();

        $this->righe = $this->ordine->righe->map(fn($r) => [
            'ordine_riga_id'    => $r->id,
            'descrizione'       => $r->descrizione,
            'codice_fornitore'  => $r->codice_fornitore,
            'quantita_ordinata' => $r->quantita_ordinata,
            'quantita_ricevuta_totale' => $r->quantita_ricevuta,
            'da_ricevere'       => $r->quantitaDaRicevere(),
            'quantita_ricevuta' => $r->quantitaDaRicevere(),
            'prezzo_unitario'   => $r->prezzo_unitario_atteso,
            'articolo_id'       => $r->articolo_id,
        ])->toArray();
    }

    public function confermaRicezione(): void
    {
        $this->validate();

        $action = app(RicezioneMerceAction::class);

        $action->execute(
            ordine: $this->ordine,
            numeroDdt: $this->numeroDdt,
            dataDdt: $this->dataDdt,
            dataRicezione: $this->dataRicezione,
            righe: $this->righe,
            utente: auth()->user(),
            note: $this->note ?: null,
        );

        session()->flash('success', 'Ricezione merce registrata. Magazzino aggiornato.');
        $this->redirect(route('acquisti.ordini.show', $this->ordine->id));
    }

    public function render()
    {
        return view('livewire.acquisti.ricezione-merce');
    }
}
