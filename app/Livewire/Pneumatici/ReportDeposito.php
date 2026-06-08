<?php

namespace App\Livewire\Pneumatici;

use App\Enums\StagionePneumatico;
use App\Enums\StatoPneumatico;
use App\Models\DepositoPneumatico;
use App\Models\Pneumatico;
use Livewire\Component;
use Livewire\WithPagination;

class ReportDeposito extends Component
{
    use WithPagination;

    public string $filtroStagione  = '';
    public string $filtroUbicazione = '';
    public string $tab             = 'corrente';  // corrente|smaltiti

    public function updatedFiltroStagione(): void { $this->resetPage(); }
    public function updatedFiltroUbicazione(): void { $this->resetPage(); }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pneumatici = $this->queryDeposito()->get();

        $bom = "\xEF\xBB\xBF";
        return response()->streamDownload(function () use ($pneumatici, $bom) {
            echo $bom;
            echo "Cliente;Targa;Stagione;Marca;Misura;Ubicazione;Data deposito;Giorni in deposito;Usura%\n";
            foreach ($pneumatici as $p) {
                $ult = $p->movimenti->first();
                $giorni = $ult ? $ult->data_azione->diffInDays(now()) : '';
                echo implode(';', [
                    $p->cliente?->nome_completo ?? '',
                    $p->veicolo?->targa ?? '',
                    $p->stagione->label(),
                    $p->marca,
                    $p->misura,
                    $ult?->ubicazione ?? '',
                    $ult ? $ult->data_azione->format('d/m/Y') : '',
                    $giorni,
                    $ult?->usura_percentuale ?? '',
                ]) . "\n";
            }
        }, 'report-deposito-' . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function queryDeposito()
    {
        return Pneumatico::where('stato', StatoPneumatico::InDeposito)
            ->with(['cliente', 'veicolo', 'movimenti'])
            ->when($this->filtroStagione, fn($q) => $q->where('stagione', $this->filtroStagione))
            ->when($this->filtroUbicazione, function ($q) {
                $q->whereHas('movimenti', fn($m) =>
                    $m->where('ubicazione', 'like', '%' . $this->filtroUbicazione . '%')
                );
            });
    }

    public function render()
    {
        $correnti = $this->queryDeposito()->paginate(25);

        $countPerStagione = [];
        foreach (StagionePneumatico::cases() as $s) {
            $countPerStagione[$s->value] = Pneumatico::where('stato', StatoPneumatico::InDeposito)
                ->where('stagione', $s)
                ->count();
        }

        // Set in deposito da più di 180 giorni
        $inAttesa = Pneumatico::where('stato', StatoPneumatico::InDeposito)
            ->whereHas('movimenti', fn($q) =>
                $q->where('data_azione', '<=', now()->subDays(180)->toDateString())
            )
            ->count();

        // Smaltiti nell'anno corrente
        $smaltiti = Pneumatico::where('stato', StatoPneumatico::Smaltito)
            ->whereYear('updated_at', now()->year)
            ->with(['cliente', 'veicolo', 'movimenti'])
            ->latest('updated_at')
            ->paginate(25);

        // Mappa scaffali
        $mappaScaffali = $this->calcolaMappaScaffali();

        return view('livewire.pneumatici.report-deposito', compact(
            'correnti', 'countPerStagione', 'inAttesa', 'smaltiti', 'mappaScaffali'
        ));
    }

    private function calcolaMappaScaffali(): array
    {
        // Prende le ubicazioni dal DB e costruisce la griglia
        $ubicazioni = DepositoPneumatico::whereNotNull('ubicazione')
            ->whereHas('pneumatico', fn($q) => $q->where('stato', StatoPneumatico::InDeposito))
            ->with('pneumatico.veicolo', 'pneumatico.cliente')
            ->get()
            ->groupBy('ubicazione');

        $griglia = [];
        foreach ($ubicazioni as $ubicazione => $movimenti) {
            // Pattern "Scaffale A3" o semplicemente "A3"
            if (preg_match('/([A-Z])(\d+)/i', $ubicazione, $m)) {
                $col = strtoupper($m[1]);
                $row = (int)$m[2];
                $griglia[$col][$row] = [
                    'ubicazione' => $ubicazione,
                    'occupato'   => true,
                    'info'       => $movimenti->map(fn($mov) => [
                        'targa'   => $mov->pneumatico?->veicolo?->targa ?? '',
                        'cliente' => $mov->pneumatico?->cliente?->nome_completo ?? '',
                    ])->first(),
                ];
            }
        }

        ksort($griglia);
        foreach ($griglia as &$rows) {
            ksort($rows);
        }

        return $griglia;
    }
}
