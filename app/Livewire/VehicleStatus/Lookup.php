<?php

namespace App\Livewire\VehicleStatus;

use App\Services\VehicleStatus\VehicleStatusService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Lookup extends Component
{
    public string $ricerca = '';

    #[Computed]
    public function results(): Collection
    {
        return app(VehicleStatusService::class)->lookup($this->ricerca);
    }

    public function render()
    {
        $results = $this->results;

        $autoExpandId = null;
        if ($results->count() === 1) {
            $match = $results->first();
            if (strtoupper(trim($this->ricerca)) === $match->targa) {
                $autoExpandId = $match->veicoloId;
            }
        }

        return view('livewire.vehicle-status.lookup', [
            'results'      => $results,
            'autoExpandId' => $autoExpandId,
        ])->layout('layouts.app', ['title' => 'Stato Veicolo']);
    }
}
