<?php

namespace App\Services\Analytics;

use App\Enums\StatoCommessa;
use App\Enums\TipoRiga;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MeccaniciService
{
    /** Produttività per meccanico nel periodo */
    public function produttivita(Carbon $da, Carbon $a): array
    {
        $costoOrarioDefault = (float) Setting::get('costo_orario_default', 30);

        $meccanici = User::role('meccanico')->get();
        $risultati = [];

        foreach ($meccanici as $mec) {
            $risultati[] = $this->calcolaPerMeccanico($mec, $da, $a, $costoOrarioDefault);
        }

        // Ordina per ricavo generato desc
        usort($risultati, fn($a, $b) => $b['ricavo_generato'] <=> $a['ricavo_generato']);

        return $risultati;
    }

    private function calcolaPerMeccanico(User $mec, Carbon $da, Carbon $a, float $costoDefault): array
    {
        // Ore lavorate (lavorazioni chiuse nel periodo)
        $minutiLavorati = (int) DB::table('lavorazioni')
            ->where('user_id', $mec->id)
            ->whereNotNull('stopped_at')
            ->whereBetween('stopped_at', [$da, $a])
            ->sum('minuti_effettivi');

        $oreLavorate = round($minutiLavorati / 60, 2);

        // Commesse in cui il meccanico ha lavorazioni nel periodo
        $commessaIds = DB::table('lavorazioni')
            ->where('user_id', $mec->id)
            ->whereNotNull('stopped_at')
            ->whereBetween('stopped_at', [$da, $a])
            ->pluck('commessa_id')
            ->unique();

        // Ore fatturate: righe manodopera delle commesse completate
        $oreFatturate = 0.0;
        $ricavoGenerato = 0.0;

        if ($commessaIds->isNotEmpty()) {
            $righe = DB::table('commessa_righe')
                ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
                ->where('commessa_righe.tipo', TipoRiga::Manodopera->value)
                ->whereIn('commesse.stato', [
                    StatoCommessa::Completata->value,
                    StatoCommessa::Consegnata->value,
                    StatoCommessa::Fatturata->value,
                ])
                ->whereIn('commessa_righe.commessa_id', $commessaIds)
                ->whereNull('commesse.deleted_at')
                ->select(
                    DB::raw('SUM(commessa_righe.quantita) as ore'),
                    DB::raw('SUM(commessa_righe.quantita * commessa_righe.prezzo_unitario * (1 - commessa_righe.sconto_percentuale/100)) as ricavo')
                )
                ->first();

            $oreFatturate  = (float) ($righe->ore ?? 0);
            $ricavoGenerato = (float) ($righe->ricavo ?? 0);
        }

        $costoOrario = $mec->costo_orario ? (float) $mec->costo_orario : $costoDefault;
        $costo       = round($oreLavorate * $costoOrario, 2);
        $margine     = round($ricavoGenerato - $costo, 2);
        $efficienza  = $oreLavorate > 0
            ? round($oreFatturate / $oreLavorate * 100, 1)
            : 0.0;

        return [
            'id'              => $mec->id,
            'nome'            => $mec->name,
            'ore_lavorate'    => $oreLavorate,
            'ore_fatturate'   => $oreFatturate,
            'efficienza'      => $efficienza,
            'ricavo_generato' => round($ricavoGenerato, 2),
            'costo'           => $costo,
            'margine'         => $margine,
        ];
    }

    /** Dati grafico barre raggruppate ore lavorate vs fatturate */
    public function grafico(Carbon $da, Carbon $a): array
    {
        $dati   = $this->produttivita($da, $a);
        $nomi   = array_column($dati, 'nome');
        $lavorate  = array_column($dati, 'ore_lavorate');
        $fatturate = array_column($dati, 'ore_fatturate');

        return [
            'labels'    => $nomi,
            'lavorate'  => $lavorate,
            'fatturate' => $fatturate,
        ];
    }
}
