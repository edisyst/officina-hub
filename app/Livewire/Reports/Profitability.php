<?php

namespace App\Livewire\Reports;

use App\Enums\StatoCommessa;
use App\Enums\TipoRiga;
use App\Models\Commessa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

// Note: uses DB::raw for aggregates; cross-DB compat via concatExpr() helper

class Profitability extends Component
{
    use WithPagination;

    public string $dal = '';
    public string $al  = '';
    public string $meccanico_id = '';
    public string $stato = '';

    public function mount(): void
    {
        $this->dal = now()->startOfMonth()->format('Y-m-d');
        $this->al  = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingDal(): void { $this->resetPage(); }
    public function updatingAl(): void { $this->resetPage(); }
    public function updatingMeccanicoId(): void { $this->resetPage(); }
    public function updatingStato(): void { $this->resetPage(); }

    private function baseQuery(): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('commesse')
            ->whereNull('commesse.deleted_at')
            ->whereBetween('commesse.data_ingresso', [$this->dal . ' 00:00:00', $this->al . ' 23:59:59']);

        if ($this->stato !== '') {
            $q->where('commesse.stato', $this->stato);
        }

        if ($this->meccanico_id !== '') {
            $q->whereExists(function ($sub) {
                $sub->from('lavorazioni')
                    ->whereColumn('lavorazioni.commessa_id', 'commesse.id')
                    ->where('lavorazioni.user_id', (int) $this->meccanico_id);
            });
        }

        return $q;
    }

    public function totaliPeriodo(): array
    {
        $ids = $this->baseQuery()->pluck('commesse.id');

        $righe = DB::table('commessa_righe')
            ->whereIn('commessa_id', $ids)
            ->select(
                DB::raw("SUM(CASE WHEN tipo != 'nota' THEN quantita * prezzo_unitario * (1 - sconto_percentuale/100) ELSE 0 END) as ricavo_totale"),
                DB::raw("SUM(CASE WHEN tipo = 'manodopera' THEN quantita * prezzo_unitario * (1 - sconto_percentuale/100) ELSE 0 END) as ricavo_mano"),
                DB::raw("SUM(CASE WHEN tipo != 'manodopera' AND tipo != 'nota' THEN quantita * COALESCE(NULLIF(prezzo_acquisto,0), 0) ELSE 0 END) as costo_ricambi"),
                DB::raw("SUM(CASE WHEN tipo = 'manodopera' THEN COALESCE(ore_preventivate,0) ELSE 0 END) as ore_prev_tot"),
            )
            ->first();

        $lavorazioni = DB::table('lavorazioni')
            ->whereIn('commessa_id', $ids)
            ->whereNotNull('minuti_effettivi')
            ->selectRaw('SUM(minuti_effettivi)/60.0 as ore_eff_tot')
            ->first();

        $ricavoTotale = (float) ($righe->ricavo_totale ?? 0);
        $costoRicambi = (float) ($righe->costo_ricambi ?? 0);
        $margine      = $ricavoTotale - $costoRicambi;

        return [
            'commesse_count'  => $ids->count(),
            'ricavo_totale'   => round($ricavoTotale, 2),
            'costo_ricambi'   => round($costoRicambi, 2),
            'margine'         => round($margine, 2),
            'margine_perc'    => $ricavoTotale > 0 ? round($margine / $ricavoTotale * 100, 1) : 0.0,
            'ore_prev'        => round((float) ($righe->ore_prev_tot ?? 0), 1),
            'ore_eff'         => round((float) ($lavorazioni->ore_eff_tot ?? 0), 1),
        ];
    }

    public function meccaniciReport(): \Illuminate\Support\Collection
    {
        $ids = $this->baseQuery()->pluck('commesse.id');

        $orePerMeccanico = DB::table('lavorazioni')
            ->whereIn('commessa_id', $ids)
            ->join('users', 'users.id', '=', 'lavorazioni.user_id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(lavorazioni.minuti_effettivi)/60.0 as ore_eff'),
            )
            ->whereNotNull('lavorazioni.minuti_effettivi')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');

        $orePreventivate = DB::table('lavorazioni')
            ->whereIn('commessa_id', $ids)
            ->join('users', 'users.id', '=', 'lavorazioni.user_id')
            ->select(
                'users.id',
                DB::raw('SUM(lavorazioni.minuti_preventivati)/60.0 as ore_prev'),
            )
            ->whereNotNull('lavorazioni.minuti_preventivati')
            ->groupBy('users.id')
            ->get()
            ->keyBy('id');

        $ricavoPerMeccanico = DB::table('commessa_righe')
            ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
            ->join('lavorazioni', 'lavorazioni.commessa_id', '=', 'commesse.id')
            ->join('users', 'users.id', '=', 'lavorazioni.user_id')
            ->whereIn('commesse.id', $ids)
            ->where('commessa_righe.tipo', TipoRiga::Manodopera->value)
            ->select(
                'users.id',
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) as ricavo_mano'),
            )
            ->groupBy('users.id')
            ->get()
            ->keyBy('id');

        return $orePerMeccanico->map(function ($row) use ($orePreventivate, $ricavoPerMeccanico) {
            $oreEff  = round((float) $row->ore_eff, 1);
            $orePrev = round((float) ($orePreventivate[$row->id]?->ore_prev ?? 0), 1);
            $efficienza = ($oreEff > 0 && $orePrev > 0) ? round($orePrev / $oreEff * 100, 1) : null;
            return [
                'id'         => $row->id,
                'name'       => $row->name,
                'ore_eff'    => $oreEff,
                'ore_prev'   => $orePrev,
                'efficienza' => $efficienza,
                'ricavo_mano'=> round((float) ($ricavoPerMeccanico[$row->id]?->ricavo_mano ?? 0), 2),
            ];
        })->sortByDesc('ore_eff')->values();
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view-margins');

        $ids = $this->baseQuery()->pluck('commesse.id');

        $rows = DB::table('commesse')
            ->whereIn('commesse.id', $ids)
            ->leftJoin('clienti', 'clienti.id', '=', 'commesse.cliente_id')
            ->leftJoin('commessa_righe', 'commessa_righe.commessa_id', '=', 'commesse.id')
            ->select(
                'commesse.id as commessa_id',
                'commesse.numero',
                'commesse.stato',
                DB::raw("COALESCE(" . $this->concatExpr('clienti.nome', ' ', 'clienti.cognome') . ", '') as cliente"),
                DB::raw("SUM(CASE WHEN commessa_righe.tipo != 'nota' AND commessa_righe.tipo IS NOT NULL THEN commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100) ELSE 0 END) as ricavo"),
                DB::raw("SUM(CASE WHEN commessa_righe.tipo = 'manodopera' THEN COALESCE(commessa_righe.ore_preventivate,0) ELSE 0 END) as ore_prev"),
            )
            ->groupBy('commesse.id', 'commesse.numero', 'commesse.stato', 'clienti.nome', 'clienti.cognome')
            ->get();

        $lav = DB::table('lavorazioni')
            ->whereIn('commessa_id', $ids)
            ->whereNotNull('minuti_effettivi')
            ->select('commessa_id', DB::raw('SUM(minuti_effettivi)/60.0 as ore_eff'))
            ->groupBy('commessa_id')
            ->get()
            ->keyBy('commessa_id');

        return response()->streamDownload(function () use ($rows, $lav) {
            echo "\xEF\xBB\xBF";
            echo "Numero;Stato;Cliente;Ricavo;Ore Prev.;Ore Eff.\n";
            foreach ($rows as $r) {
                $oreEff = round((float) ($lav[$r->commessa_id]?->ore_eff ?? 0), 2);
                echo implode(';', [
                    $r->numero,
                    $r->stato,
                    '"' . str_replace('"', '""', $r->cliente) . '"',
                    number_format((float) $r->ricavo, 2, ',', ''),
                    number_format((float) $r->ore_prev, 2, ',', ''),
                    number_format($oreEff, 2, ',', ''),
                ]) . "\n";
            }
        }, 'redditivita_' . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function commesseReport(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $ids = $this->baseQuery()->pluck('commesse.id');

        return DB::table('commesse')
            ->whereIn('commesse.id', $ids)
            ->leftJoin('clienti', 'clienti.id', '=', 'commesse.cliente_id')
            ->leftJoin('veicoli', 'veicoli.id', '=', 'commesse.veicolo_id')
            ->leftJoin('commessa_righe', 'commessa_righe.commessa_id', '=', 'commesse.id')
            ->select(
                'commesse.id',
                'commesse.numero',
                'commesse.stato',
                'commesse.data_ingresso',
                DB::raw("COALESCE(" . $this->concatExpr('clienti.nome', ' ', 'clienti.cognome') . ", '') as cliente"),
                DB::raw("COALESCE(veicoli.targa, '') as targa"),
                DB::raw("SUM(CASE WHEN commessa_righe.tipo != 'nota' AND commessa_righe.tipo IS NOT NULL THEN commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100) ELSE 0 END) as ricavo"),
                DB::raw("SUM(CASE WHEN commessa_righe.tipo = 'manodopera' THEN COALESCE(commessa_righe.ore_preventivate,0) ELSE 0 END) as ore_prev"),
            )
            ->groupBy('commesse.id', 'commesse.numero', 'commesse.stato', 'commesse.data_ingresso', 'clienti.nome', 'clienti.cognome', 'veicoli.targa')
            ->orderByDesc('commesse.data_ingresso')
            ->paginate(20);
    }

    private function concatExpr(string $a, string $sep, string $b): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "{$a} || '{$sep}' || {$b}";
        }
        return "CONCAT({$a}, '{$sep}', {$b})";
    }

    public function render()
    {
        $this->authorize('view-margins');

        $meccanici = User::role('meccanico')->orderBy('name')->get(['id', 'name']);

        return view('livewire.reports.profitability', [
            'totali'      => $this->totaliPeriodo(),
            'meccanici'   => $meccanici,
            'mecRep'      => $this->meccaniciReport(),
            'commesseRep' => $this->commesseReport(),
            'stati'       => StatoCommessa::cases(),
        ]);
    }
}
