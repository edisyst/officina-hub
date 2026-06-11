<?php

namespace App\Livewire\Fatturazione;

use App\Models\FatturaAcquisto;
use App\Models\RegistroIva as RegistroIvaModel;
use Livewire\Component;

class RegistroIva extends Component
{
    public string $tab        = 'vendite';
    public string $filtroMese = '';
    public string $filtroAnno = '';

    public function mount(): void
    {
        $this->filtroMese = now()->format('m');
        $this->filtroAnno = (string) now()->year;
    }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = $this->buildQuery();

        $nome = "registro-{$this->tab}-{$this->filtroAnno}-{$this->filtroMese}.csv";

        return response()->streamDownload(function () use ($query) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Data', 'Numero', 'Cliente/Fornitore', 'P.IVA', 'C.F.', 'Aliquota %', 'Natura', 'Imponibile', 'IVA', 'Totale'], ';');

            foreach ($query->cursor() as $r) {
                fputcsv($handle, [
                    $r->data_registrazione->format('d/m/Y'),
                    $r->numero_documento,
                    $r->cliente_fornitore,
                    $r->partita_iva,
                    $r->codice_fiscale,
                    number_format((float) $r->aliquota_iva, 2, ',', '.'),
                    $r->natura_iva,
                    number_format((float) $r->imponibile, 2, ',', '.'),
                    number_format((float) $r->iva, 2, ',', '.'),
                    number_format((float) $r->totale, 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery()
    {
        return RegistroIvaModel::where('tipo_registro', $this->tab)
            ->when($this->filtroAnno, fn($q) => $q->whereYear('data_registrazione', $this->filtroAnno))
            ->when($this->filtroMese, fn($q) => $q->whereMonth('data_registrazione', $this->filtroMese))
            ->orderBy('data_registrazione')
            ->orderBy('numero_documento');
    }

    public function render()
    {
        $righe = $this->buildQuery()->get();

        $totali = [
            'imponibile' => $righe->sum(fn($r) => (float) $r->imponibile),
            'iva'        => $righe->sum(fn($r) => (float) $r->iva),
            'totale'     => $righe->sum(fn($r) => (float) $r->totale),
        ];

        $anni = RegistroIvaModel::selectRaw('YEAR(data_registrazione) as anno')
            ->distinct()
            ->orderByDesc('anno')
            ->pluck('anno');

        return view('livewire.fatturazione.registro-iva', compact('righe', 'totali', 'anni'));
    }
}
