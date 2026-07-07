<?php

namespace App\Livewire\Commesse;

use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\Commessa;
use Livewire\Component;
use Livewire\WithPagination;

class ListaCommesse extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filtroStato = '';
    public string $filtroTipo = '';
    public string $vista = 'tabella'; // tabella | kanban

    public function mount(): void
    {
        if (session('commesse_vista') === 'board') {
            $this->redirect(route('commesse.board'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingVista(): void
    {
        session(['commesse_vista' => 'tabella']);
    }

    public function render()
    {
        $query = Commessa::query()
            ->with(['cliente', 'veicolo'])
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->filtroTipo, fn($q) => $q->where('tipo', $this->filtroTipo))
            ->latest('data_ingresso');

        if ($this->vista === 'kanban') {
            $commessePerStato = [];
            foreach (StatoCommessa::cases() as $stato) {
                $commessePerStato[$stato->value] = (clone $query)
                    ->where('stato', $stato->value)
                    ->limit(20)
                    ->get();
            }
            $commesse = collect();
        } else {
            $commesse = $query->paginate(20);
            $commessePerStato = [];
        }

        return view('livewire.commesse.lista-commesse', [
            'commesse' => $commesse,
            'commessePerStato' => $commessePerStato,
            'stati' => StatoCommessa::cases(),
            'tipi' => TipoCommessa::cases(),
        ]);
    }
}
