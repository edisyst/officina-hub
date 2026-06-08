<?php

namespace App\Livewire\Cortesia;

use App\Enums\StatoPrestito;
use App\Models\PrestitoCortesia;
use App\Models\VeicoloCortesia;
use Livewire\Component;

class ReportCortesia extends Component
{
    public string $filtroAnno = '';

    public function mount(): void
    {
        $this->filtroAnno = (string) now()->year;
    }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $prestiti = $this->queryBase()->with(['veicolo', 'cliente'])->get();

        $bom = "\xEF\xBB\xBF";
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="report-cortesia-' . $this->filtroAnno . '.csv"',
        ];

        return response()->streamDownload(function () use ($prestiti, $bom) {
            $out = fopen('php://output', 'w');
            fwrite($out, $bom);
            fputcsv($out, ['Targa veicolo', 'Cliente', 'Data consegna', 'Data rientro prevista', 'Data rientro effettiva', 'Km consegna', 'Km rientro', 'Km percorsi', 'Carb. consegna %', 'Carb. rientro %', 'Cauzione €', 'Stato'], ';');
            foreach ($prestiti as $p) {
                fputcsv($out, [
                    $p->veicolo->targa,
                    $p->cliente->nome_completo,
                    $p->data_consegna->format('d/m/Y H:i'),
                    $p->data_rientro_prevista->format('d/m/Y'),
                    $p->data_rientro_effettiva?->format('d/m/Y H:i') ?? '',
                    $p->km_consegna,
                    $p->km_rientro ?? '',
                    $p->km_percorsi ?? '',
                    $p->carburante_consegna,
                    $p->carburante_rientro ?? '',
                    number_format($p->cauzione_importo, 2, ',', '.'),
                    $p->stato->label(),
                ], ';');
            }
            fclose($out);
        }, 'report-cortesia-' . $this->filtroAnno . '.csv', $headers);
    }

    private function queryBase()
    {
        return PrestitoCortesia::query()
            ->when($this->filtroAnno, fn($q) => $q->whereYear('data_consegna', $this->filtroAnno));
    }

    public function render()
    {
        $anno = $this->filtroAnno ?: now()->year;

        $utilizzoPerVeicolo = VeicoloCortesia::withoutTrashed()
            ->get()
            ->map(function ($v) use ($anno) {
                $prestiti = $v->prestiti()
                    ->whereYear('data_consegna', $anno)
                    ->where('stato', StatoPrestito::Rientrato)
                    ->whereNotNull('km_rientro')
                    ->get();

                return [
                    'veicolo'          => $v,
                    'n_prestiti'       => $prestiti->count(),
                    'giorni_prestati'  => $prestiti->sum(fn($p) => $p->data_consegna->diffInDays($p->data_rientro_effettiva ?? now())),
                    'km_totali'        => $prestiti->sum('km_percorsi'),
                    'cauzione_totale'  => $prestiti->where('cauzione_pagata', true)->sum('cauzione_importo'),
                ];
            });

        $prestitiInRitardo = PrestitoCortesia::inRitardo()
            ->with(['veicolo', 'cliente'])
            ->get();

        $anni = PrestitoCortesia::selectRaw('YEAR(data_consegna) as anno')
            ->groupBy('anno')
            ->orderByDesc('anno')
            ->pluck('anno');

        return view('livewire.cortesia.report-cortesia', compact(
            'utilizzoPerVeicolo', 'prestitiInRitardo', 'anni'
        ));
    }
}
