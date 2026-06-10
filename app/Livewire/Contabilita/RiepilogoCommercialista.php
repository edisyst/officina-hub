<?php

namespace App\Livewire\Contabilita;

use App\Enums\FormatoExportContabile;
use App\Enums\StatoDocumento;
use App\Enums\TipoPrimaNota;
use App\Models\Documento;
use App\Models\Pagamento;
use App\Models\PrimaNota;
use App\Models\RegistroIva;
use App\Models\Setting;
use App\Services\Export\CsvGenericoFormatter;
use App\Services\Export\DatagammaFormatter;
use App\Services\Export\TeamSystemFormatter;
use App\Services\Export\ZucchettiFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class RiepilogoCommercialista extends Component
{
    public string $filtroDal  = '';
    public string $filtroAl   = '';

    public function mount(): void
    {
        $this->filtroDal = now()->startOfMonth()->format('Y-m-d');
        $this->filtroAl  = now()->endOfMonth()->format('Y-m-d');
    }

    public function generaPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $dati    = $this->raccogliDati();
        $settings = Setting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('pdf.riepilogo-commercialista', array_merge($dati, ['settings' => $settings]))
            ->setPaper('a4', 'portrait');

        $nome = 'riepilogo-commercialista-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $nome,
            ['Content-Type' => 'application/pdf']
        );
    }

    /** Export registro IVA nel formato configurato in settings */
    public function esportaRegistroIva(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $righe   = $this->queryRegistroIva()->get();
        $formato = FormatoExportContabile::from(setting('export_contabile_formato', 'csv_generico'));

        [$contenuto, $mimeType, $estensione] = match($formato) {
            FormatoExportContabile::CsvGenerico  => [
                app(CsvGenericoFormatter::class)->formatta($righe),
                'text/csv; charset=UTF-8',
                'csv',
            ],
            FormatoExportContabile::PrimaNotaTxt,
            FormatoExportContabile::TeamSystem   => [
                app(TeamSystemFormatter::class)->formatta($righe),
                'text/plain; charset=UTF-8',
                'txt',
            ],
            FormatoExportContabile::Zucchetti    => [
                app(ZucchettiFormatter::class)->formatta($righe),
                'text/csv; charset=UTF-8',
                'csv',
            ],
            FormatoExportContabile::Datagamma    => [
                app(DatagammaFormatter::class)->formatta($righe),
                'application/xml',
                'xml',
            ],
        };

        $nome = "registro-iva-{$formato->value}-" . now()->format('Y-m') . ".{$estensione}";

        return response()->streamDownload(
            fn() => print($contenuto),
            $nome,
            ['Content-Type' => $mimeType]
        );
    }

    private function raccogliDati(): array
    {
        $dal = $this->filtroDal;
        $al  = $this->filtroAl;

        // Fatture emesse nel periodo
        $documenti = Documento::with(['cliente', 'righe'])
            ->whereDate('data_emissione', '>=', $dal)
            ->whereDate('data_emissione', '<=', $al)
            ->whereNotIn('stato', [StatoDocumento::Annullata->value])
            ->get();

        // Riepilogo per aliquota
        $righeIva = RegistroIva::where('tipo_registro', 'vendite')
            ->whereDate('data_registrazione', '>=', $dal)
            ->whereDate('data_registrazione', '<=', $al)
            ->get();

        $perAliquota = $righeIva->groupBy(fn($r) => $r->natura_iva ?: ((int)$r->aliquota_iva . '%'))
            ->map(fn($righe) => [
                'imponibile' => $righe->sum(fn($r) => (float) $r->imponibile),
                'iva'        => $righe->sum(fn($r) => (float) $r->iva),
                'totale'     => $righe->sum(fn($r) => (float) $r->totale),
            ]);

        // Pagamenti incassati
        $pagamenti = Pagamento::whereHas('documento', function ($q) use ($dal, $al) {
            $q->whereDate('data_emissione', '>=', $dal)->whereDate('data_emissione', '<=', $al);
        })->whereDate('data_pagamento', '>=', $dal)
          ->whereDate('data_pagamento', '<=', $al)
          ->get();

        $totalePagato   = $pagamenti->sum(fn($p) => (float) $p->importo);
        $totaleDocumenti = $documenti->sum(fn($d) => (float) $d->totale_documento);
        $insoluto       = max(0, $totaleDocumenti - $totalePagato);

        // Prima nota del periodo
        $movimentiPrimaNota = PrimaNota::whereDate('data', '>=', $dal)
            ->whereDate('data', '<=', $al)
            ->orderBy('data')
            ->get();

        // Registro IVA
        $registroIva = $righeIva->sortBy('data_registrazione')->values();

        return compact(
            'dal', 'al',
            'documenti', 'perAliquota',
            'totalePagato', 'totaleDocumenti', 'insoluto',
            'movimentiPrimaNota', 'registroIva'
        );
    }

    private function queryRegistroIva()
    {
        return RegistroIva::where('tipo_registro', 'vendite')
            ->when($this->filtroDal, fn($q) => $q->whereDate('data_registrazione', '>=', $this->filtroDal))
            ->when($this->filtroAl,  fn($q) => $q->whereDate('data_registrazione', '<=', $this->filtroAl))
            ->orderBy('data_registrazione')
            ->orderBy('numero_documento');
    }

    public function render()
    {
        $dati     = $this->raccogliDati();
        $formati  = FormatoExportContabile::cases();
        $formatoAttuale = setting('export_contabile_formato', 'csv_generico');

        return view('livewire.contabilita.riepilogo-commercialista', array_merge($dati, [
            'formati'       => $formati,
            'formatoAttuale' => $formatoAttuale,
        ]));
    }
}
