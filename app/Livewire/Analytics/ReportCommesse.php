<?php

namespace App\Livewire\Analytics;

use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\User;
use App\Services\Analytics\CsvExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportCommesse extends Component
{
    use WithPagination;

    public string $periodo    = 'questo_mese';
    public string $dataDa     = '';
    public string $dataA      = '';
    public string $filtroStato    = '';
    public string $filtroTipo     = '';
    public string $filtroMeccanico = '';

    // Dati grafico tempi medi
    public array $graficoTempi = [];

    public function mount(): void
    {
        $this->aggiornaGrafico();
    }

    public function updatedPeriodo(): void
    {
        if ($this->periodo !== 'personalizzato') {
            $this->aggiornaGrafico();
        }
        $this->resetPage();
    }

    public function updatedFiltroStato(): void    { $this->resetPage(); }
    public function updatedFiltroTipo(): void     { $this->resetPage(); }
    public function updatedFiltroMeccanico(): void { $this->resetPage(); }

    public function applicaFiltroPersonalizzato(): void
    {
        $this->aggiornaGrafico();
        $this->resetPage();
    }

    private function aggiornaGrafico(): void
    {
        [$da, $a] = $this->dateRange();

        $stati = [
            StatoCommessa::Completata->value,
            StatoCommessa::Consegnata->value,
            StatoCommessa::Fatturata->value,
        ];

        $tempi = DB::table('commesse')
            ->whereIn('stato', $stati)
            ->whereBetween('data_consegna', [$da, $a])
            ->whereNotNull('data_consegna')
            ->whereNotNull('data_ingresso')
            ->whereNull('deleted_at')
            ->select(
                'tipo',
                DB::raw('MIN(DATEDIFF(data_consegna, data_ingresso)) as minimo'),
                DB::raw('AVG(DATEDIFF(data_consegna, data_ingresso)) as media'),
                DB::raw('MAX(DATEDIFF(data_consegna, data_ingresso)) as massimo')
            )
            ->groupBy('tipo')
            ->get();

        $labels = [];
        $minimi = [];
        $medie  = [];
        $massimi = [];

        foreach ($tempi as $row) {
            $labels[]  = \App\Enums\TipoCommessa::from($row->tipo)->label();
            $minimi[]  = (float) $row->minimo;
            $medie[]   = round((float) $row->media, 1);
            $massimi[] = (float) $row->massimo;
        }

        $this->graficoTempi = compact('labels', 'minimi', 'medie', 'massimi');
        $this->dispatch('tempi-chart-aggiornato', graficoTempi: $this->graficoTempi);
    }

    private function queryCommesse()
    {
        [$da, $a] = $this->dateRange();

        return DB::table('commesse')
            ->leftJoin('clienti', 'clienti.id', '=', 'commesse.cliente_id')
            ->leftJoin('veicoli', 'veicoli.id', '=', 'commesse.veicolo_id')
            ->leftJoin('users', 'users.id', '=', 'commesse.user_id')
            ->leftJoin(DB::raw('(SELECT commessa_id, SUM(quantita * prezzo_unitario * (1 - sconto_percentuale/100)) as totale_preventivato FROM commessa_righe GROUP BY commessa_id) cr'), 'cr.commessa_id', '=', 'commesse.id')
            ->leftJoin(DB::raw('(SELECT commessa_id, SUM(totale) as totale_fatturato FROM documenti WHERE tipo = \'fattura\' AND deleted_at IS NULL GROUP BY commessa_id) doc'), 'doc.commessa_id', '=', 'commesse.id')
            ->whereNull('commesse.deleted_at')
            ->whereBetween('commesse.data_ingresso', [$da, $a])
            ->when($this->filtroStato, fn($q) => $q->where('commesse.stato', $this->filtroStato))
            ->when($this->filtroTipo, fn($q) => $q->where('commesse.tipo', $this->filtroTipo))
            ->when($this->filtroMeccanico, fn($q) => $q->where('commesse.user_id', $this->filtroMeccanico))
            ->select(
                'commesse.id',
                'commesse.numero',
                'commesse.stato',
                'commesse.tipo',
                'commesse.data_ingresso',
                'commesse.data_consegna',
                'users.name as meccanico_nome',
                DB::raw("CONCAT(COALESCE(clienti.nome,''), ' ', COALESCE(clienti.cognome,''), COALESCE(clienti.ragione_sociale,'')) as cliente_nome"),
                DB::raw("CONCAT(COALESCE(veicoli.targa,''), ' ', COALESCE(veicoli.marca,''), ' ', COALESCE(veicoli.modello,'')) as veicolo_desc"),
                DB::raw('COALESCE(cr.totale_preventivato, 0) as totale_preventivato'),
                DB::raw('COALESCE(doc.totale_fatturato, 0) as totale_fatturato'),
                DB::raw('DATEDIFF(COALESCE(commesse.data_consegna, NOW()), commesse.data_ingresso) as giorni_attesa')
            )
            ->orderByDesc('commesse.data_ingresso');
    }

    public function esportaCsv(): Response
    {
        $commesse = $this->queryCommesse()->get();

        $intestazioni = [
            'Numero', 'Cliente', 'Veicolo', 'Data ingresso', 'Data consegna',
            'Giorni attesa', 'Tipo', 'Meccanico', 'Preventivato (€)', 'Fatturato (€)', 'Stato',
        ];

        $righe = $commesse->map(fn($c) => [
            $c->numero,
            trim($c->cliente_nome),
            trim($c->veicolo_desc),
            $c->data_ingresso ? Carbon::parse($c->data_ingresso)->format('d/m/Y') : '',
            $c->data_consegna ? Carbon::parse($c->data_consegna)->format('d/m/Y') : '',
            $c->giorni_attesa,
            \App\Enums\TipoCommessa::from($c->tipo)->label(),
            $c->meccanico_nome ?? '',
            (float) $c->totale_preventivato,
            (float) $c->totale_fatturato,
            \App\Enums\StatoCommessa::from($c->stato)->label(),
        ])->toArray();

        return app(CsvExportService::class)->esporta($intestazioni, $righe, 'report_commesse');
    }

    public function render()
    {
        $commesse  = $this->queryCommesse()->paginate(25);
        $meccanici = User::role('meccanico')->orderBy('name')->get();
        $statiList = StatoCommessa::cases();
        $tipiList  = TipoCommessa::cases();

        return view('livewire.analytics.report-commesse', compact(
            'commesse', 'meccanici', 'statiList', 'tipiList'
        ));
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
}
