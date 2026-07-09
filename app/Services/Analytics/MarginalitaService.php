<?php

namespace App\Services\Analytics;

use App\Enums\StatoCommessa;
use App\Enums\TipoRiga;
use App\Models\Commessa;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarginalitaService
{
    /** Calcola marginalità per una singola commessa (metodo originale Step 2) */
    public function calcola(Commessa $commessa): array
    {
        $commessa->loadMissing(['righe', 'lavorazioni.meccanico']);

        $costoOrarioDefault = (float) Setting::get('costo_orario_default', 30);

        $ricavoManodopera = 0.0;
        $ricavoArticoli   = 0.0;

        foreach ($commessa->righe->where('outcome', '!=', 'declined') as $riga) {
            $imponibile = (float) $riga->quantita * (float) $riga->prezzo_unitario
                * (1 - (float) $riga->sconto_percentuale / 100);

            if ($riga->tipo === TipoRiga::Manodopera) {
                $ricavoManodopera += $imponibile;
            } else {
                $ricavoArticoli += $imponibile;
            }
        }

        $costoManodopera = 0.0;
        foreach ($commessa->lavorazioni as $lavorazione) {
            if ($lavorazione->minuti_effettivi === null) {
                continue;
            }
            $oreEffettive = $lavorazione->minuti_effettivi / 60;
            $costoOrario  = $lavorazione->meccanico?->costo_orario
                ? (float) $lavorazione->meccanico->costo_orario
                : $costoOrarioDefault;
            $costoManodopera += $oreEffettive * $costoOrario;
        }

        $costoArticoli = 0.0;
        foreach ($commessa->righe->where('outcome', '!=', 'declined') as $riga) {
            if ($riga->tipo !== TipoRiga::Manodopera) {
                $costoArticoli += (float) $riga->prezzo_acquisto * (float) $riga->quantita;
            }
        }

        $ricavoTotale = $ricavoManodopera + $ricavoArticoli;
        $costoTotale  = $costoManodopera + $costoArticoli;
        $margineLordo = $ricavoTotale - $costoTotale;
        $percentuale  = $ricavoTotale > 0 ? ($margineLordo / $ricavoTotale * 100) : 0.0;

        return [
            'ricavo_manodopera'   => round($ricavoManodopera, 2),
            'ricavo_articoli'     => round($ricavoArticoli, 2),
            'ricavo_totale'       => round($ricavoTotale, 2),
            'costo_manodopera'    => round($costoManodopera, 2),
            'costo_articoli'      => round($costoArticoli, 2),
            'costo_totale'        => round($costoTotale, 2),
            'margine_lordo'       => round($margineLordo, 2),
            'percentuale_margine' => round($percentuale, 1),
        ];
    }

    /** Marginalità aggregata per tipo commessa nel periodo */
    public function calcolaPerCategoria(Carbon $da, Carbon $a): array
    {
        $costoOrarioDefault = (float) Setting::get('costo_orario_default', 30);

        $stati = [
            StatoCommessa::Completata->value,
            StatoCommessa::Consegnata->value,
            StatoCommessa::Fatturata->value,
        ];

        // Ricavi per tipo commessa
        $ricavi = DB::table('commessa_righe')
            ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
            ->whereIn('commesse.stato', $stati)
            ->whereBetween('commesse.data_consegna', [$da, $a])
            ->whereNull('commesse.deleted_at')
            ->where('commessa_righe.outcome', '!=', 'declined')
            ->select(
                'commesse.tipo',
                'commessa_righe.tipo as tipo_riga',
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) as ricavo'),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_acquisto) as costo_articoli')
            )
            ->groupBy('commesse.tipo', 'commessa_righe.tipo')
            ->get();

        // Costi manodopera per tipo commessa
        $costiLav = DB::table('lavorazioni')
            ->join('commesse', 'commesse.id', '=', 'lavorazioni.commessa_id')
            ->leftJoin('users', 'users.id', '=', 'lavorazioni.user_id')
            ->whereIn('commesse.stato', $stati)
            ->whereBetween('commesse.data_consegna', [$da, $a])
            ->whereNull('commesse.deleted_at')
            ->whereNotNull('lavorazioni.minuti_effettivi')
            ->select(
                'commesse.tipo',
                DB::raw('SUM(lavorazioni.minuti_effettivi / 60 * COALESCE(users.costo_orario, ' . $costoOrarioDefault . ')) as costo_lavoro')
            )
            ->groupBy('commesse.tipo')
            ->get()
            ->keyBy('tipo');

        $risultati = [];

        foreach ($ricavi->groupBy('tipo') as $tipo => $righe) {
            $ricavoMano  = (float) $righe->where('tipo_riga', TipoRiga::Manodopera->value)->sum('ricavo');
            $ricavoArt   = (float) $righe->where('tipo_riga', '!=', TipoRiga::Manodopera->value)->sum('ricavo');
            $costoArt    = (float) $righe->where('tipo_riga', '!=', TipoRiga::Manodopera->value)->sum('costo_articoli');
            $costoLav    = (float) ($costiLav[$tipo]?->costo_lavoro ?? 0);

            $ricavoTotale = $ricavoMano + $ricavoArt;
            $costoTotale  = $costoLav + $costoArt;
            $margine      = $ricavoTotale - $costoTotale;
            $percentuale  = $ricavoTotale > 0 ? round($margine / $ricavoTotale * 100, 1) : 0.0;

            $risultati[$tipo] = [
                'tipo'            => $tipo,
                'label'           => \App\Enums\TipoCommessa::from($tipo)->label(),
                'ricavo_totale'   => round($ricavoTotale, 2),
                'costo_totale'    => round($costoTotale, 2),
                'margine_lordo'   => round($margine, 2),
                'percentuale'     => $percentuale,
            ];
        }

        return array_values($risultati);
    }

    /** Marginalità per articolo (ricavo vendita vs costo acquisto) nel periodo */
    public function calcolaPerArticoli(Carbon $da, Carbon $a, int $limit = 20): array
    {
        $stati = [
            StatoCommessa::Completata->value,
            StatoCommessa::Consegnata->value,
            StatoCommessa::Fatturata->value,
        ];

        return DB::table('commessa_righe')
            ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
            ->leftJoin('articoli', 'articoli.id', '=', 'commessa_righe.articolo_id')
            ->where('commessa_righe.tipo', TipoRiga::Articolo->value)
            ->whereIn('commesse.stato', $stati)
            ->whereBetween('commesse.data_consegna', [$da, $a])
            ->whereNull('commesse.deleted_at')
            ->select(
                'articoli.id',
                DB::raw("COALESCE(articoli.descrizione, commessa_righe.descrizione) as descrizione"),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) as ricavo'),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_acquisto) as costo'),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) - SUM(commessa_righe.quantita * commessa_righe.prezzo_acquisto) as margine')
            )
            ->groupBy('articoli.id', 'articoli.descrizione', 'commessa_righe.descrizione')
            ->orderByDesc('ricavo')
            ->limit($limit)
            ->get()
            ->map(function ($r) {
                $r->percentuale = $r->ricavo > 0
                    ? round($r->margine / $r->ricavo * 100, 1)
                    : 0.0;
                return $r;
            })
            ->toArray();
    }

    /** Trend margine mensile (ultimi N mesi) per tipo */
    public function calcolaTrendMensile(int $mesi = 6): array
    {
        $stati = [
            StatoCommessa::Completata->value,
            StatoCommessa::Consegnata->value,
            StatoCommessa::Fatturata->value,
        ];

        [$annoExpr, $meseExpr] = $this->datePartExpressions('commesse.data_consegna');

        $risultati = DB::table('commessa_righe')
            ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
            ->whereIn('commesse.stato', $stati)
            ->where('commesse.data_consegna', '>=', now()->subMonths($mesi - 1)->startOfMonth())
            ->whereNull('commesse.deleted_at')
            ->select(
                'commesse.tipo',
                DB::raw("{$annoExpr} as anno"),
                DB::raw("{$meseExpr} as mese"),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) as ricavo'),
                DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_acquisto) as costo')
            )
            ->groupBy('commesse.tipo', DB::raw($annoExpr), DB::raw($meseExpr))
            ->get();

        $mesiIt = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
        $labels = [];
        for ($i = $mesi - 1; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $labels[] = $mesiIt[(int)$d->format('m') - 1] . ' ' . $d->format('y');
        }

        $tipi = \App\Enums\TipoCommessa::cases();
        $datasets = [];

        foreach ($tipi as $tipo) {
            $valori = [];
            for ($i = $mesi - 1; $i >= 0; $i--) {
                $d = now()->subMonths($i);
                $anno = (int) $d->format('Y');
                $meseNum = (int) $d->format('m');
                $riga = $risultati->first(fn($r) =>
                    $r->tipo === $tipo->value && (int)$r->anno === $anno && (int)$r->mese === $meseNum
                );
                $ricavo = (float) ($riga?->ricavo ?? 0);
                $costo  = (float) ($riga?->costo ?? 0);
                $valori[] = round($ricavo - $costo, 2);
            }
            $datasets[] = [
                'label'  => $tipo->label(),
                'valori' => $valori,
            ];
        }

        return compact('labels', 'datasets');
    }

    /** @return array{0: string, 1: string} */
    private function datePartExpressions(string $colonna): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return [
                "CAST(strftime('%Y', {$colonna}) AS INTEGER)",
                "CAST(strftime('%m', {$colonna}) AS INTEGER)",
            ];
        }
        return ["YEAR({$colonna})", "MONTH({$colonna})"];
    }
}
