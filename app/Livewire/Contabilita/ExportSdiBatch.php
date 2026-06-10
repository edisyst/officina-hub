<?php

namespace App\Livewire\Contabilita;

use App\Models\Documento;
use App\Services\FatturaPAService;
use Livewire\Component;
use ZipArchive;

class ExportSdiBatch extends Component
{
    public string $filtroDal  = '';
    public string $filtroAl   = '';
    public string $filtroStato = '';

    /** @var array<int> */
    public array $selezionati = [];

    public bool $tuttiSelezionati = false;

    public function mount(): void
    {
        $this->filtroDal  = now()->startOfMonth()->format('Y-m-d');
        $this->filtroAl   = now()->endOfMonth()->format('Y-m-d');
        $this->filtroStato = 'emessa';
    }

    public function updatedFiltroDal(): void   { $this->selezionati = []; $this->tuttiSelezionati = false; }
    public function updatedFiltroAl(): void    { $this->selezionati = []; $this->tuttiSelezionati = false; }
    public function updatedFiltroStato(): void { $this->selezionati = []; $this->tuttiSelezionati = false; }

    public function toggleTutti(): void
    {
        if ($this->tuttiSelezionati) {
            $this->selezionati = $this->buildQuery()->pluck('id')->map(fn($id) => (int)$id)->toArray();
        } else {
            $this->selezionati = [];
        }
    }

    public function generaZip(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (empty($this->selezionati)) {
            session()->flash('error', 'Selezionare almeno un documento.');
            return response()->streamDownload(fn() => null, 'vuoto.zip');
        }

        $documenti = Documento::with(['cliente', 'righe'])
            ->whereIn('id', $this->selezionati)
            ->get();

        $service = app(FatturaPAService::class);

        return response()->streamDownload(function () use ($documenti, $service) {
            $tempZip = tempnam(sys_get_temp_dir(), 'sdi_batch_');
            $zip = new ZipArchive();
            $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $indice = "\xEF\xBB\xBF" . "File;Numero;Cliente;Data;Totale\r\n";

            foreach ($documenti as $doc) {
                try {
                    $xml      = $service->genera($doc);
                    $nomeFile = $this->nomeFileSdi($doc);
                    $zip->addFromString($nomeFile, $xml);

                    $indice .= implode(';', [
                        $nomeFile,
                        $doc->numero,
                        $doc->cliente->nome_completo ?? '',
                        $doc->data_emissione?->format('d/m/Y') ?? '',
                        number_format((float) $doc->totale_documento, 2, ',', '.'),
                    ]) . "\r\n";
                } catch (\Throwable $e) {
                    $indice .= implode(';', [
                        "ERRORE_{$doc->numero}",
                        $doc->numero,
                        $doc->cliente->nome_completo ?? '',
                        '',
                        "Errore: " . $e->getMessage(),
                    ]) . "\r\n";
                }
            }

            $zip->addFromString('indice.csv', $indice);
            $zip->close();

            readfile($tempZip);
            unlink($tempZip);
        }, 'fatture-sdi-' . now()->format('Y-m-d') . '.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function nomeFileSdi(Documento $doc): string
    {
        // Formato SdI: IT{PIVA}_{progressivo}.xml
        $piva  = config('app.name', 'IT00000000000');
        $pivaSettings = setting('officina_piva', '00000000000');
        $pivaClean = preg_replace('/[^A-Z0-9]/i', '', $pivaSettings);

        return "IT{$pivaClean}_{$doc->numero}.xml";
    }

    private function buildQuery()
    {
        return Documento::with('cliente')
            ->where('tipo', 'fattura')
            ->when($this->filtroDal,   fn($q) => $q->whereDate('data_emissione', '>=', $this->filtroDal))
            ->when($this->filtroAl,    fn($q) => $q->whereDate('data_emissione', '<=', $this->filtroAl))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->orderBy('data_emissione')
            ->orderBy('numero');
    }

    public function render()
    {
        $documenti = $this->buildQuery()->get();

        return view('livewire.contabilita.export-sdi-batch', [
            'documenti' => $documenti,
        ]);
    }
}
