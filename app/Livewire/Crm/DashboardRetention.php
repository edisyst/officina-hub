<?php

namespace App\Livewire\Crm;

use App\Enums\SegmentoCrm;
use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardRetention extends Component
{
    public string $periodoMesi = '12';

    public function getMetricheProperty(): array
    {
        $da = now()->subMonths((int) $this->periodoMesi)->startOfDay();

        $nuovi = Cliente::where('created_at', '>=', $da)->count();

        $persi = Cliente::where('segmento_crm', SegmentoCrm::Perso->value)
            ->where('updated_at', '>=', $da)
            ->count();

        $totale = Cliente::count();
        $conPiuVisite = Cliente::where('numero_visite', '>=', 2)->count();
        $tassoRitorno = $totale > 0 ? round(($conPiuVisite / $totale) * 100, 1) : 0;

        $valoreLifetimeMedio = Cliente::where('valore_lifetime', '>', 0)->avg('valore_lifetime') ?? 0;

        // Ticket medio per segmento
        $ticketPerSegmento = [];
        foreach (SegmentoCrm::cases() as $s) {
            $totaleSegmento = Cliente::where('segmento_crm', $s->value)->count();
            $valoreSegmento = Cliente::where('segmento_crm', $s->value)->sum('valore_lifetime');
            $visite         = Cliente::where('segmento_crm', $s->value)->sum('numero_visite');

            $ticketPerSegmento[$s->value] = [
                'label'          => $s->label(),
                'badge'          => $s->badgeClass(),
                'count'          => $totaleSegmento,
                'valore_totale'  => $valoreSegmento,
                'ticket_medio'   => $visite > 0 ? round($valoreSegmento / $visite, 2) : 0,
            ];
        }

        return compact('nuovi', 'persi', 'tassoRitorno', 'valoreLifetimeMedio', 'ticketPerSegmento');
    }

    public function getDistribuzioneProperty(): array
    {
        $rows = Cliente::select('segmento_crm', DB::raw('COUNT(*) as totale'))
            ->groupBy('segmento_crm')
            ->get();

        $result = [];
        foreach (SegmentoCrm::cases() as $s) {
            $result[] = [
                'label'  => $s->label(),
                'valore' => 0,
                'colore' => $s->colore(),
            ];
        }

        foreach ($rows as $row) {
            if (!$row->segmento_crm) continue;
            foreach ($result as &$item) {
                try {
                    $enum = SegmentoCrm::from($row->segmento_crm);
                    if ($item['label'] === $enum->label()) {
                        $item['valore'] = $row->totale;
                    }
                } catch (\ValueError) {}
            }
        }

        return $result;
    }

    public function getNuoviPerMeseProperty(): array
    {
        $dati = Cliente::select(
            DB::raw($this->yearExpr() . ' as anno'),
            DB::raw($this->monthExpr() . ' as mese'),
            DB::raw('COUNT(*) as totale')
        )
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy(DB::raw($this->yearExpr()), DB::raw($this->monthExpr()))
            ->orderBy('anno')
            ->orderBy('mese')
            ->get();

        // Costruisce array per tutti i 12 mesi
        $labels  = [];
        $valori  = [];
        $mesi    = collect();

        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $mesi->push(['anno' => (int) $d->format('Y'), 'mese' => (int) $d->format('n'), 'label' => $d->format('M Y')]);
        }

        foreach ($mesi as $m) {
            $labels[] = $m['label'];
            $trovato  = $dati->first(fn($r) => (int) $r->anno === $m['anno'] && (int) $r->mese === $m['mese']);
            $valori[] = $trovato ? $trovato->totale : 0;
        }

        return compact('labels', 'valori');
    }

    public function getDaRecuperareProperty()
    {
        return Cliente::where('segmento_crm', SegmentoCrm::ARischio->value)
            ->orderByDesc('valore_lifetime')
            ->with(['veicoli'])
            ->limit(20)
            ->get();
    }

    private function yearExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', created_at)"
            : 'YEAR(created_at)';
    }

    private function monthExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%m', created_at)"
            : 'MONTH(created_at)';
    }

    public function render()
    {
        return view('livewire.crm.dashboard-retention', [
            'metriche'      => $this->metriche,
            'distribuzione' => $this->distribuzione,
            'nuoviPerMese'  => $this->nuoviPerMese,
            'daRecuperare'  => $this->daRecuperare,
        ]);
    }
}
