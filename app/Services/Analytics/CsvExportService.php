<?php

namespace App\Services\Analytics;

use Illuminate\Http\Response;

class CsvExportService
{
    /**
     * Genera StreamedResponse CSV con BOM UTF-8 per compatibilità Excel.
     *
     * @param array $intestazioni   Nomi colonne
     * @param array $righe          Array di array con i valori
     * @param string $nomeFile      Nome file senza estensione
     */
    public function esporta(array $intestazioni, array $righe, string $nomeFile): Response
    {
        $separatore = ';';

        $lines = [];
        $lines[] = implode($separatore, array_map(
            fn($v) => '"' . str_replace('"', '""', $v) . '"',
            $intestazioni
        ));

        foreach ($righe as $riga) {
            $cells = [];
            foreach ($riga as $valore) {
                if (is_float($valore) || is_int($valore)) {
                    $cells[] = number_format($valore, 2, ',', '');
                } else {
                    $cells[] = '"' . str_replace('"', '""', (string) ($valore ?? '')) . '"';
                }
            }
            $lines[] = implode($separatore, $cells);
        }

        // BOM UTF-8 per Excel italiano
        $csv = "\xEF\xBB\xBF" . implode("\r\n", $lines);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nomeFile . '_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
