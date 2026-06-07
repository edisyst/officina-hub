<?php

namespace App\Livewire\Analytics;

use App\Services\Analytics\CsvExportService;
use App\Services\Analytics\MeccaniciService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ProduttivitaMeccanici extends Component
{
    public string $periodo  = 'questo_mese';
    public string $dataDa   = '';
    public string $dataA    = '';

    public array $datiMeccanici  = [];
    public array $grafico        = [];

    // Base64 immagine grafico per PDF (popolato via JS)
    public string $graficoPng = '';

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
        $service = app(MeccaniciService::class);

        $this->datiMeccanici = $service->produttivita($da, $a);
        $this->grafico       = $service->grafico($da, $a);

        $this->dispatch('meccanici-chart-aggiornato', grafico: $this->grafico);
    }

    public function esportaCsv(): Response
    {
        $intestazioni = [
            'Meccanico', 'Ore lavorate', 'Ore fatturate', 'Efficienza %',
            'Ricavo generato (€)', 'Costo (€)', 'Margine (€)',
        ];

        $righe = array_map(fn($m) => [
            $m['nome'],
            $m['ore_lavorate'],
            $m['ore_fatturate'],
            $m['efficienza'],
            $m['ricavo_generato'],
            $m['costo'],
            $m['margine'],
        ], $this->datiMeccanici);

        return app(CsvExportService::class)->esporta($intestazioni, $righe, 'produttivita_meccanici');
    }

    public function esportaPdf(): Response
    {
        [$da, $a] = $this->dateRange();

        $pdf = app(\Barryvdh\DomPDF\Facade\Pdf::class)
            ->loadView('pdf.analytics.produttivita-meccanici', [
                'dati'       => $this->datiMeccanici,
                'graficoPng' => $this->graficoPng,
                'periodoLabel' => $this->labelPeriodo(),
                'da'         => $da->format('d/m/Y'),
                'a'          => $a->format('d/m/Y'),
            ])
            ->setPaper('A4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="produttivita_meccanici_' . now()->format('Ymd') . '.pdf"',
        ]);
    }

    private function labelPeriodo(): string
    {
        return match($this->periodo) {
            'questo_mese'      => 'Questo mese',
            'mese_scorso'      => 'Mese scorso',
            'questo_trimestre' => 'Questo trimestre',
            'questo_anno'      => 'Questo anno',
            'personalizzato'   => $this->dataDa . ' — ' . $this->dataA,
            default            => 'Questo mese',
        };
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
        return view('livewire.analytics.produttivita-meccanici');
    }
}
