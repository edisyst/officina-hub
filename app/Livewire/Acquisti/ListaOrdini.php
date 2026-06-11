<?php

namespace App\Livewire\Acquisti;

use App\Enums\StatoOrdineFornitore;
use App\Models\Fornitore;
use App\Models\OrdineFornitore;
use Livewire\Component;
use Livewire\WithPagination;

class ListaOrdini extends Component
{
    use WithPagination;

    public string $search          = '';
    public string $filtroFornitore = '';
    public string $filtroStato     = '';
    public string $filtroAnno      = '';

    public function mount(): void
    {
        $this->filtroAnno = (string) now()->year;
    }

    public function updatedSearch(): void          { $this->resetPage(); }
    public function updatedFiltroFornitore(): void { $this->resetPage(); }
    public function updatedFiltroStato(): void     { $this->resetPage(); }
    public function updatedFiltroAnno(): void      { $this->resetPage(); }

    public function render()
    {
        $query = OrdineFornitore::with('fornitore')
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroFornitore, fn($q) => $q->where('fornitore_id', $this->filtroFornitore))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->filtroAnno, fn($q) => $q->where('anno', $this->filtroAnno))
            ->orderByDesc('id');

        return view('livewire.acquisti.lista-ordini', [
            'ordini'    => $query->paginate(20),
            'fornitori' => Fornitore::orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
            'stati'     => StatoOrdineFornitore::cases(),
            'anni'      => OrdineFornitore::selectRaw('DISTINCT anno')->orderByDesc('anno')->pluck('anno'),
        ]);
    }
}
