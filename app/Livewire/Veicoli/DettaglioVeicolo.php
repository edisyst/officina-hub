<?php

namespace App\Livewire\Veicoli;

use App\Livewire\Concerns\TracksRecentView;
use App\Models\Veicolo;
use Livewire\Component;

class DettaglioVeicolo extends Component
{
    use TracksRecentView;
    public Veicolo $veicolo;
    public string $tabAttiva = 'storico';

    public function mount(int $veicoloId): void
    {
        $this->veicolo = Veicolo::withTrashed()->findOrFail($veicoloId);

        $this->trackRecentView($this->veicolo);
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

        $countPneumaticiDeposito = $this->veicolo->pneumatici()
            ->where('stato', 'in_deposito')
            ->count();

        $countGaranzieAttive = $this->veicolo->garanzie()->attive()->count();
        $garanzieInScadenza  = $this->veicolo->garanzie()->attive()->get()->filter(fn($g) => $g->isInScadenza());

        return view('livewire.veicoli.dettaglio-veicolo', compact(
            'proprietari', 'commesse', 'countPneumaticiDeposito',
            'countGaranzieAttive', 'garanzieInScadenza'
        ));
    }
}
