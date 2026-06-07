<?php

namespace App\Livewire\Analytics;

use App\Models\CommessaRiga;
use App\Models\PacchettoServizio;
use Livewire\Component;

class PacchettiReport extends Component
{
    public string $periodo = 'mese';

    public function render()
    {
        $topPacchetti = PacchettoServizio::withTrashed()
            ->orderByDesc('utilizzi')
            ->take(10)
            ->get()
            ->map(function ($p) {
                $ricavo = CommessaRiga::where('pacchetto_servizio_id', $p->id)
                    ->get()
                    ->sum(fn($r) => $r->imponibile);
                return [
                    'nome'     => $p->nome,
                    'utilizzi' => $p->utilizzi,
                    'ricavo'   => $ricavo,
                    'attivo'   => $p->attivo,
                ];
            });

        // Tariffe fuori listino (> 15% scostamento)
        $tariffeFuoriListino = CommessaRiga::where('tipo', 'manodopera')
            ->whereNotNull('tariffa_manodopera_id')
            ->with(['commessa:id,numero,cliente_id', 'commessa.cliente:id,nome,cognome', 'tariffa:id,codice,descrizione,prezzo_listino'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->filter(function ($r) {
                if (! $r->tariffa || (float) $r->tariffa->prezzo_listino == 0) {
                    return false;
                }
                $scostamento = abs((float) $r->prezzo_unitario - (float) $r->tariffa->prezzo_listino)
                    / (float) $r->tariffa->prezzo_listino;
                return $scostamento > 0.15;
            })
            ->map(function ($r) {
                $listino = (float) $r->tariffa->prezzo_listino;
                $applicato = (float) $r->prezzo_unitario;
                $scostamento = $listino > 0 ? (($applicato - $listino) / $listino) * 100 : 0;
                return [
                    'commessa_numero' => $r->commessa?->numero,
                    'cliente'         => $r->commessa?->cliente?->nome_completo ?? '—',
                    'codice_tariffa'  => $r->tariffa->codice,
                    'descrizione'     => $r->descrizione,
                    'prezzo_listino'  => $listino,
                    'prezzo_applicato'=> $applicato,
                    'scostamento_pct' => round($scostamento, 1),
                ];
            })
            ->sortByDesc(fn($r) => abs($r['scostamento_pct']))
            ->values()
            ->take(50);

        return view('livewire.analytics.pacchetti-report', compact('topPacchetti', 'tariffeFuoriListino'));
    }
}
