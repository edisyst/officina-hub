<?php

namespace App\Livewire\Analytics;

use App\Services\Analytics\CsvExportService;
use App\Services\Analytics\MarginalitaService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Livewire\Component;

class MarginalitaCategorie extends Component
{
    public string $periodo = 'questo_mese';
    public string $dataDa  = '';
    public string $dataA   = '';

    public array $perCategoria = [];
    public array $perArticoli  = [];
    public array $trendMensile = [];
    public array $graficoTorta = [];

    public function mount(): void
    {
        $this->aggiornaDati();
    }

    public function updatedPeriodo(): void
    {
        if ($this->periodo !== 'personalizzato') {
            $this->aggiornaDati();
        }
    }

    public function applicaFiltroPersonalizzato(): void
    {
        $this->aggiornaDati();
    }

    public function aggiornaDati(): void
    {
        [$da, $a] = $this->dateRange();
        $service = app(MarginalitaService::class);

        $this->perCategoria = $service->calcolaPerCategoria($da, $a);
        $this->perArticoli  = json_decode(json_encode($service->calcolaPerArticoli($da, $a)), true);
        $this->trendMensile = $service->calcolaTrendMensile(6);

        // Dati grafico torta
        $this->graficoTorta = [
            'labels' => array_column($this->perCategoria, 'label'),
            'valori' => array_column($this->perCategoria, 'margine_lordo'),
            'colori' => ['rgba(60,141,188,0.8)', 'rgba(243,156,18,0.8)', 'rgba(40,167,69,0.8)'],
        ];

        $this->dispatch('marginalita-chart-aggiornato',
            graficoTorta: $this->graficoTorta,
            trendMensile: $this->trendMensile,
        );
    }

    public function esportaCsvCategorie(): Response
    {
        $intestazioni = ['Categoria', 'Fatturato (€)', 'Costo (€)', 'Margine (€)', 'Margine %'];
        $righe = array_map(fn($r) => [
            $r['label'], $r['ricavo_totale'], $r['costo_totale'], $r['margine_lordo'], $r['percentuale'],
        ], $this->perCategoria);

        return app(CsvExportService::class)->esporta($intestazioni, $righe, 'marginalita_categorie');
    }

    public function esportaCsvArticoli(): Response
    {
        $intestazioni = ['Articolo', 'Ricavo (€)', 'Costo (€)', 'Margine (€)', 'Margine %'];
        $righe = array_map(fn($r) => [
            $r['descrizione'], $r['ricavo'], $r['costo'], $r['margine'], $r['percentuale'],
        ], $this->perArticoli);

        return app(CsvExportService::class)->esporta($intestazioni, $righe, 'marginalita_articoli');
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
            default => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    public function render()
    {
        return view('livewire.analytics.marginalita-categorie');
    }
}
