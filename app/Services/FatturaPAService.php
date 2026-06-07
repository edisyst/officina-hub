<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Setting;
use App\Models\SdiLog;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FatturaPAService
{
    /**
     * Genera la stringa XML FatturaPA da template Blade.
     * Il chiamante deve invocare valida() prima di considerare l'XML definitivo.
     */
    public function genera(Documento $documento): string
    {
        $documento->load(['cliente', 'righe', 'commessa']);
        $settings = Setting::pluck('value', 'key')->all();

        // Raggruppa le righe per aliquota IVA (o natura) per il blocco DatiRiepilogo
        $riepilogi = $documento->righe
            ->groupBy(function ($riga) {
                return $riga->natura_iva
                    ? 'N_' . $riga->natura_iva
                    : number_format((float) $riga->iva_percentuale, 2, '.', '');
            })
            ->map(function ($righe, $chiave) {
                return [
                    'aliquota'  => str_starts_with($chiave, 'N_') ? '0.00' : $chiave,
                    'natura'    => str_starts_with($chiave, 'N_') ? substr($chiave, 2) : null,
                    'imponibile' => $righe->sum(fn($r) => (float) $r->imponibile_riga),
                    'iva'       => $righe->sum(fn($r) => (float) $r->iva_riga),
                ];
            })
            ->values();

        $xml = view('fatturapa.fattura', compact('documento', 'settings', 'riepilogi'))->render();
        $xml = trim($xml);

        // Rimuovi BOM UTF-8 se il template lo ha aggiunto
        if (str_starts_with($xml, "\xEF\xBB\xBF")) {
            $xml = substr($xml, 3);
        }

        return $xml;
    }

    /**
     * Valida la stringa XML contro lo XSD ufficiale AdE (Schema_VFPR12.xsd).
     * Restituisce ['valido' => bool, 'errori' => array<string>].
     */
    public function valida(string $xml): array
    {
        $xsdPath = storage_path('app/fatturapa/xsd/Schema_VFPR12.xsd');

        if (!file_exists($xsdPath)) {
            return [
                'valido' => false,
                'errori' => [
                    'File XSD non trovato: ' . $xsdPath . '. '
                    . 'Scaricare Schema_VFPR12.xsd dall\'Agenzia delle Entrate '
                    . '(https://www.agenziaentrate.gov.it/portale/specifiche-tecniche-versione-1.9) '
                    . 'e salvarlo in storage/app/fatturapa/xsd/',
                ],
            ];
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $valido = $dom->schemaValidate($xsdPath);
        $errori = [];

        if (!$valido) {
            foreach (libxml_get_errors() as $errore) {
                $livello = match($errore->level) {
                    LIBXML_ERR_WARNING => 'Avviso',
                    LIBXML_ERR_ERROR   => 'Errore',
                    LIBXML_ERR_FATAL   => 'Fatale',
                    default            => 'Info',
                };
                $errori[] = "{$livello} (riga {$errore->line}): " . trim($errore->message);
            }
            libxml_clear_errors();
        }

        return ['valido' => $valido, 'errori' => $errori];
    }

    /** Genera il nome file SdI: IT{PIVA}_FPR12_{PROGRESSIVO}.xml */
    public function nomeFile(Documento $documento): string
    {
        $piva = preg_replace('/[^0-9A-Z]/', '', strtoupper(Setting::get('officina_piva', '00000000000')));
        $progressivo = str_pad($documento->progressivo, 5, '0', STR_PAD_LEFT);

        return "IT{$piva}_FPR12_{$progressivo}.xml";
    }

    /**
     * Crea un file ZIP contenente l'XML già generato, pronto per l'upload
     * manuale al portale "Fatture e Corrispettivi" dell'AdE.
     * Restituisce il percorso assoluto dello ZIP temporaneo.
     */
    public function pacchetto(Documento $documento): string
    {
        if (empty($documento->xml_generato)) {
            throw new \RuntimeException('XML non ancora generato per il documento ' . $documento->numero);
        }

        $nomeXml = $documento->nome_file_sdi;
        $tempDir = storage_path('app/fatturapa/temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/' . pathinfo($nomeXml, PATHINFO_FILENAME) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossibile creare il file ZIP in ' . $zipPath);
        }

        $zip->addFromString($nomeXml, $documento->xml_generato);
        $zip->close();

        return $zipPath;
    }

    /** Registra un evento nel log SdI */
    public function log(Documento $documento, string $azione, string $esito, ?string $dettaglio = null): void
    {
        SdiLog::create([
            'documento_id' => $documento->id,
            'azione'       => $azione,
            'esito'        => $esito,
            'dettaglio'    => $dettaglio,
            'created_at'   => now(),
        ]);

        if ($esito === 'errore') {
            Log::warning("FatturaPA [{$azione}] documento #{$documento->id}: {$dettaglio}");
        }
    }
}
