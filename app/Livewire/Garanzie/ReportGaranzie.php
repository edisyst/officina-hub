<?php

namespace App\Livewire\Garanzie;

use App\Models\CasaMadre;
use App\Models\CommessaRiga;
use App\Models\Documento;
use App\Enums\StatoDocumento;
use App\Enums\TipoEmissione;
use Carbon\Carbon;
use Livewire\Component;

class ReportGaranzie extends Component
{
    public string $dal   = '';
    public string $al    = '';
    public ?int $casaMadreId = null;

    public function mount(): void
    {
        $this->dal = now()->startOfMonth()->toDateString();
        $this->al  = now()->endOfMonth()->toDateString();
    }

    public function esportaCsv(): void
    {
        $dati = $this->calcolaDati();

        $csv  = "\xEF\xBB\xBF"; // BOM UTF-8
        $csv .= "Casa Madre;Totale Rimborsi Attesi (€);Totale Incassati (€);Delta (€);Fatture Emesse;Fatture Pagate\n";

        foreach ($dati['per_casa_madre'] as $row) {
            $csv .= implode(';', [
                $row['ragione_sociale'],
                number_format($row['rimborsi_attesi'], 2, ',', '.'),
                number_format($row['incassati'], 2, ',', '.'),
                number_format($row['delta'], 2, ',', '.'),
                $row['fatture_emesse'],
                $row['fatture_pagate'],
            ]) . "\n";
        }

        response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'report-garanzie-' . $this->dal . '-' . $this->al . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->send();
    }

    private function calcolaDati(): array
    {
        $dal = Carbon::parse($this->dal)->startOfDay();
        $al  = Carbon::parse($this->al)->endOfDay();

        // Righe in garanzia nel periodo
        $righeGaranzia = CommessaRiga::with(['garanzia', 'casaMadre', 'commessa'])
            ->where('in_garanzia', true)
            ->whereHas('commessa', fn($q) => $q->whereBetween('data_ingresso', [$dal, $al]))
            ->get();

        $valoreClientePerso = $righeGaranzia->sum(fn($r) => $r->totale);
        $countInterventi    = $righeGaranzia->pluck('commessa_id')->unique()->count();

        // Fatture verso case madri nel periodo
        $fattureQuery = Documento::with('casaMadre')
            ->where('tipo_emissione', TipoEmissione::CasaMadre)
            ->whereBetween('data_emissione', [$dal->toDateString(), $al->toDateString()]);

        if ($this->casaMadreId) {
            $fattureQuery->where('casa_madre_id', $this->casaMadreId);
        }

        $fatture = $fattureQuery->get();

        $perCasaMadre = [];
        foreach ($fatture->groupBy('casa_madre_id') as $cmId => $docs) {
            $cm = $docs->first()->casaMadre;
            $rimborsiAttesi = $docs->sum('totale');
            $incassati      = $docs->sum('totale_pagato');
            $perCasaMadre[] = [
                'casa_madre_id'     => $cmId,
                'ragione_sociale'   => $cm?->ragione_sociale ?? 'Casa madre non specificata',
                'rimborsi_attesi'   => $rimborsiAttesi,
                'incassati'         => $incassati,
                'delta'             => $rimborsiAttesi - $incassati,
                'fatture_emesse'    => $docs->count(),
                'fatture_pagate'    => $docs->where('stato', StatoDocumento::Pagata)->count(),
                'fatture_scadute'   => $docs->filter(fn($d) =>
                    $d->data_scadenza && $d->data_scadenza->isPast()
                    && ! in_array($d->stato->value, ['pagata'])
                )->count(),
                'documenti'         => $docs,
            ];
        }

        return [
            'count_interventi'      => $countInterventi,
            'valore_cliente_perso'  => $valoreClientePerso,
            'totale_da_rimborsare'  => $fatture->sum('totale'),
            'totale_rimborsato'     => $fatture->sum('totale_pagato'),
            'per_casa_madre'        => $perCasaMadre,
        ];
    }

    public function render()
    {
        $dati      = $this->calcolaDati();
        $caseMadri = CasaMadre::orderBy('ragione_sociale')->get();

        return view('livewire.garanzie.report-garanzie', compact('dati', 'caseMadri'));
    }
}
