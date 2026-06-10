<?php

namespace App\Services\Export;

use App\Models\RegistroIva;
use Illuminate\Support\Collection;

class CsvGenericoFormatter
{
    /**
     * Genera il CSV generico del registro IVA vendite.
     * Encoding UTF-8 con BOM, separatore ;, date dd/mm/yyyy, importi con virgola decimale.
     */
    public function formatta(Collection $righe): string
    {
        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 per compatibilità Excel italiano
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Data', 'Numero', 'Tipo', 'Cliente', 'CF/PIVA',
            'Imponibile', 'IVA', 'Totale', 'Aliquota', 'Natura', 'Metodo pagamento',
        ], ';');

        foreach ($righe as $riga) {
            fputcsv($handle, [
                $riga->data_registrazione->format('d/m/Y'),
                $riga->numero_documento,
                'Vendita',
                $riga->cliente_fornitore,
                $riga->partita_iva ?: $riga->codice_fiscale,
                number_format((float) $riga->imponibile, 2, ',', '.'),
                number_format((float) $riga->iva, 2, ',', '.'),
                number_format((float) $riga->totale, 2, ',', '.'),
                $riga->aliquota_iva ? number_format((float) $riga->aliquota_iva, 0) . '%' : '',
                $riga->natura_iva ?: '',
                '',
            ], ';');
        }

        rewind($handle);
        $contenuto = stream_get_contents($handle);
        fclose($handle);

        return $contenuto;
    }
}
