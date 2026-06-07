<?php

namespace App\Livewire\Analytics;

use App\Services\Analytics\KpiService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DashboardKpi extends Component
{
    public string $periodo = 'questo_mese';
    public string $dataDa = '';
    public string $dataA  = '';

    // KPI cards
    public float  $fatturato          = 0.0;
    public float  $deltaFatturato     = 0.0;
    public float  $ticketMedio        = 0.0;
    public float  $deltaTicketMedio   = 0.0;
    public array  $commesseAperte     = [];
    public array  $oreEfficienza      = [];
    public array  $sparkline          = [];

    // Chart data (passati come JSON alle view Alpine)
    public array $graficoFatturato   = [];
    public array $graficoCommesse    = [];

    // Tabelle
    public array $topClienti         = [];
    public array $articoliConsumati  = [];

    // Widget operativi (real-time, no periodo)
    public array $widgetOperativi    = [];

    public function mount(): void
    {
        $this->aggiornaPeriodo();
    }

    public function updatedPeriodo(): void
    {
        if ($this->periodo !== 'personalizzato') {
            $this->aggiornaPeriodo();
        }
    }

    public function applicaFiltroPersonalizzato(): void
    {
        $this->aggiornaPeriodo();
    }

    public function aggiornaPeriodo(): void
    {
        [$da, $a] = $this->dateRange();

        $service = app(KpiService::class);

        $this->fatturato         = $service->fatturatoPeriodo($da, $a);
        $this->deltaFatturato    = $service->deltaVsAnnoPrecedente($this->fatturato, $da, $a);
        $this->ticketMedio       = $service->ticketMedio($da, $a);
        $this->deltaTicketMedio  = $service->deltaTicketMedioPeriodoPrecedente($da, $a);
        $this->commesseAperte    = $service->commesseAperte();
        $this->oreEfficienza     = $service->orePeriodo($da, $a);
        $this->sparkline         = $service->sparklineFatturato();
        $this->graficoFatturato  = $service->graficoFatturato();
        $this->graficoCommesse   = $service->distribuzioneCommesse();
        $this->topClienti        = json_decode(json_encode($service->topClienti($da, $a)), true);
        $this->articoliConsumati = json_decode(json_encode($service->articoliPiuConsumati($da, $a)), true);
        $this->widgetOperativi   = $service->widgetOperativi();

        // Aggiorna Chart.js nel browser via evento
        $this->dispatch('dashboard-charts-aggiornati',
            graficoFatturato: $this->graficoFatturato,
            graficoCommesse: $this->graficoCommesse,
        );
    }

    private function dateRange(): array
    {
        return match($this->periodo) {
            'questo_mese'      => [now()->startOfMonth(), now()->endOfDay()],
            'mese_scorso'      => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ],
            'questo_trimestre' => [now()->startOfQuarter(), now()->endOfDay()],
            'questo_anno'      => [now()->startOfYear(), now()->endOfDay()],
            'personalizzato'   => [
                Carbon::parse($this->dataDa ?: now()->startOfMonth()->toDateString())->startOfDay(),
                Carbon::parse($this->dataA  ?: now()->toDateString())->endOfDay(),
            ],
            default            => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    public function render()
    {
        return view('livewire.analytics.dashboard-kpi');
    }
}
