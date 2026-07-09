<?php

namespace App\Livewire\Recommendations;

use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\VehicleRecommendation;
use App\Services\Recommendations\RecommendationEngineService;
use Livewire\Component;

class Panel extends Component
{
    public int $commessaId;
    public bool $showDismissModal = false;
    public ?int $dismissingId     = null;
    public string $dismissReason  = '';

    public function mount(int $commessaId): void
    {
        $this->commessaId = $commessaId;
        $this->refreshEngine();
    }

    public function refreshEngine(): void
    {
        $commessa = Commessa::find($this->commessaId);
        if ($commessa?->veicolo_id) {
            app(RecommendationEngineService::class)->refreshFor($commessa->veicolo);
        }
    }

    public function addToWorkOrder(int $recommendationId): void
    {
        $rec      = VehicleRecommendation::findOrFail($recommendationId);
        $commessa = Commessa::findOrFail($this->commessaId);

        $maxOrdinamento = CommessaRiga::where('commessa_id', $this->commessaId)->max('ordinamento') ?? 0;

        CommessaRiga::create([
            'commessa_id'        => $this->commessaId,
            'tipo'               => 'manodopera',
            'descrizione'        => $rec->title,
            'quantita'           => 1,
            'prezzo_unitario'    => 0,
            'sconto_percentuale' => 0,
            'iva_percentuale'    => 22,
            'ore_preventivate'   => 0,
            'ordinamento'        => $maxOrdinamento + 1,
            'in_garanzia'        => false,
            'outcome'            => 'completed',
        ]);

        $rec->update([
            'status'                  => 'accepted',
            'resolved_work_order_id'  => $this->commessaId,
        ]);

        $this->dispatch('riga-aggiunta');
        session()->flash('success', "Aggiunto all'OdL: {$rec->title}");
    }

    public function openDismiss(int $recommendationId): void
    {
        $this->dismissingId  = $recommendationId;
        $this->dismissReason = '';
        $this->showDismissModal = true;
    }

    public function confirmDismiss(): void
    {
        if (! $this->dismissingId) {
            return;
        }

        $rec = VehicleRecommendation::findOrFail($this->dismissingId);
        $rec->update([
            'status'           => 'dismissed',
            'dismissed_reason' => $this->dismissReason ?: null,
        ]);

        $this->showDismissModal = false;
        $this->dismissingId    = null;
        $this->dismissReason   = '';
    }

    public function render()
    {
        $commessa = Commessa::find($this->commessaId);
        $recommendations = $commessa?->veicolo
            ? VehicleRecommendation::where('vehicle_id', $commessa->veicolo_id)
                ->where('status', 'pending')
                ->whereNull('deleted_at')
                ->with('originWorkOrder')
                ->orderBy('source')
                ->orderBy('created_at')
                ->get()
            : collect();

        return view('livewire.recommendations.panel', [
            'recommendations' => $recommendations,
        ]);
    }
}
