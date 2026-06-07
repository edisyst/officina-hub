<?php

namespace App\Livewire\Veicoli;

use App\Models\Veicolo;
use Livewire\Component;

class DettaglioVeicolo extends Component
{
    public Veicolo $veicolo;

    public function mount(int $veicoloId): void
    {
        $this->veicolo = Veicolo::withTrashed()->findOrFail($veicoloId);
    }

    public function render()
    {
        $proprietari = $this->veicolo->clienti()
            ->withPivot(['proprietario_attuale', 'data_inizio', 'data_fine'])
            ->get();

        $commesse = $this->veicolo->commesse()
            ->with('cliente')
            ->latest('data_ingresso')
            ->get();

        return view('livewire.veicoli.dettaglio-veicolo', compact('proprietari', 'commesse'));
    }
}
