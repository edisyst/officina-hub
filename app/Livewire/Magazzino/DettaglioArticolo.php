<?php

namespace App\Livewire\Magazzino;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Actions\Magazzino\RettificaInventarioAction;
use App\Enums\TipoMovimento;
use App\Models\Articolo;
use Livewire\Component;
use Livewire\WithPagination;

class DettaglioArticolo extends Component
{
    use WithPagination;

    public Articolo $articolo;

    // Modal carico/reso
    public bool $showCaricoModal = false;
    public string $caricoTipo = 'carico';
    public int $caricoQuantita = 1;
    public ?float $caricoPrezzoUnitario = null;
    public string $caricoDocumento = '';
    public string $caricoDataDocumento = '';
    public string $caricoNote = '';

    // Modal rettifica
    public bool $showRettificaModal = false;
    public int $nuovaGiacenza = 0;
    public string $rettificaNota = '';

    public function mount(int $articoloId): void
    {
        $this->articolo = Articolo::with(['categoria', 'fornitore'])->findOrFail($articoloId);
    }

    public function apriCaricoModal(string $tipo = 'carico'): void
    {
        $this->caricoTipo = $tipo;
        $this->caricoQuantita = 1;
        $this->caricoPrezzoUnitario = null;
        $this->caricoDocumento = '';
        $this->caricoDataDocumento = '';
        $this->caricoNote = '';
        $this->resetValidation();
        $this->showCaricoModal = true;
    }

    public function eseguiCarico(): void
    {
        $this->validate([
            'caricoQuantita'      => 'required|integer|min:1',
            'caricoPrezzoUnitario' => 'nullable|numeric|min:0',
            'caricoDocumento'     => 'nullable|string|max:100',
            'caricoDataDocumento' => 'nullable|date',
            'caricoNote'          => 'nullable|string',
        ]);

        $this->authorize('movimenta', $this->articolo);

        app(CaricoManualeAction::class)->execute(
            articolo: $this->articolo,
            tipo: TipoMovimento::from($this->caricoTipo),
            quantita: $this->caricoQuantita,
            utente: auth()->user(),
            prezzoUnitario: $this->caricoPrezzoUnitario,
            documentoFornitore: $this->caricoDocumento ?: null,
            dataDocumento: $this->caricoDataDocumento ? new \DateTime($this->caricoDataDocumento) : null,
            note: $this->caricoNote ?: null,
        );

        $this->articolo->refresh();
        $this->showCaricoModal = false;
        session()->flash('success', 'Movimento registrato.');
    }

    public function apriRettificaModal(): void
    {
        $this->nuovaGiacenza = $this->articolo->giacenza_attuale;
        $this->rettificaNota = '';
        $this->resetValidation();
        $this->showRettificaModal = true;
    }

    public function eseguiRettifica(): void
    {
        $this->validate([
            'nuovaGiacenza' => 'required|integer|min:0',
            'rettificaNota' => 'required|string|min:5',
        ]);

        $this->authorize('movimenta', $this->articolo);

        app(RettificaInventarioAction::class)->execute(
            articolo: $this->articolo,
            nuovaGiacenza: $this->nuovaGiacenza,
            utente: auth()->user(),
            nota: $this->rettificaNota,
        );

        $this->articolo->refresh();
        $this->showRettificaModal = false;
        session()->flash('success', 'Rettifica registrata.');
    }

    public function render()
    {
        $movimenti = $this->articolo->movimenti()
            ->with(['commessa', 'user'])
            ->paginate(10);

        $tipiCarico = [
            TipoMovimento::Carico,
            TipoMovimento::ResoFornitore,
            TipoMovimento::ResoCliente,
        ];

        return view('livewire.magazzino.dettaglio-articolo', compact('movimenti', 'tipiCarico'));
    }
}
