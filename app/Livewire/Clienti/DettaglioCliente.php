<?php

namespace App\Livewire\Clienti;

use App\Models\Cliente;
use App\Models\Veicolo;
use Livewire\Component;

class DettaglioCliente extends Component
{
    public Cliente $cliente;
    public string $searchVeicolo = '';
    public bool $showAssociaModal = false;
    public ?int $veicoloSelezionatoId = null;

    public function mount(int $clienteId): void
    {
        $this->cliente = Cliente::withTrashed()->findOrFail($clienteId);
    }

    public function dissocia(int $veicoloId): void
    {
        $this->authorize('update', $this->cliente);
        $this->cliente->veicoli()->detach($veicoloId);
        $this->cliente->refresh();
        session()->flash('success', 'Veicolo dissociato.');
    }

    public function apriAssociaModal(): void
    {
        $this->searchVeicolo = '';
        $this->veicoloSelezionatoId = null;
        $this->showAssociaModal = true;
    }

    public function associa(): void
    {
        $this->authorize('update', $this->cliente);

        if (! $this->veicoloSelezionatoId) {
            $this->addError('veicoloSelezionatoId', 'Seleziona un veicolo.');
            return;
        }

        $this->cliente->veicoli()->syncWithoutDetaching([
            $this->veicoloSelezionatoId => [
                'proprietario_attuale' => true,
                'data_inizio' => now()->toDateString(),
            ],
        ]);

        $this->cliente->refresh();
        $this->showAssociaModal = false;
        session()->flash('success', 'Veicolo associato con successo.');
    }

    public function render()
    {
        $veicoli = $this->cliente->veicoli()->withPivot(['proprietario_attuale', 'data_inizio', 'data_fine'])->get();

        $veicoliDisponibili = Veicolo::query()
            ->when($this->searchVeicolo, fn($q) => $q->search($this->searchVeicolo))
            ->whereNotIn('id', $veicoli->pluck('id'))
            ->limit(10)
            ->get();

        return view('livewire.clienti.dettaglio-cliente', compact('veicoli', 'veicoliDisponibili'));
    }
}
