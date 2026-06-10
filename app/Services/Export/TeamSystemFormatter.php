<?php

namespace App\Services\Export;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Tracciato TeamSystem Studio — formato interfaccia prima nota.
 * Basato sulle specifiche pubbliche di interfaccia TeamSystem Studio v.2.x.
 * I codici conto sono configurabili in Impostazioni > Export contabile.
 *
 * TODO: verificare la versione del tracciato con il commercialista prima del primo import.
 */
class TeamSystemFormatter
{
    private array $settings;

    public function __construct()
    {
        $this->settings = Setting::pluck('value', 'key')->all();
    }

    /**
     * Genera il tracciato TeamSystem per le fatture nel registro IVA.
     * Record tipo R (registrazione IVA) a larghezza fissa.
     */
    public function formatta(Collection $righe): string
    {
        $output = '';

        $contoClienti  = $this->settings['export_contabile_codice_conto_clienti']  ?? '15000';
        $contoVendite  = $this->settings['export_contabile_codice_conto_vendite']   ?? '70000';
        $contoIva      = $this->settings['export_contabile_codice_conto_iva_vendite'] ?? '26000';

        foreach ($righe as $riga) {
            $tipoRec = str_pad('F', 2);                                          // F=Fattura, N=Nota credito
            $data    = $riga->data_registrazione->format('d/m/Y');              // 10 char
            $numero  = str_pad($riga->numero_documento, 20);                     // 20 char
            $cliente = str_pad(mb_substr($riga->cliente_fornitore, 0, 40), 40); // 40 char
            $piva    = str_pad($riga->partita_iva ?? '', 16);                   // 16 char
            $impon   = str_pad(number_format((float) $riga->imponibile, 2, ',', ''), 15, ' ', STR_PAD_LEFT);
            $iva     = str_pad(number_format((float) $riga->iva, 2, ',', ''), 15, ' ', STR_PAD_LEFT);
            $totale  = str_pad(number_format((float) $riga->totale, 2, ',', ''), 15, ' ', STR_PAD_LEFT);
            $aliquota = str_pad((string)(int)$riga->aliquota_iva, 3, ' ', STR_PAD_LEFT);
            $cContoV  = str_pad($contoVendite, 10);
            $cContoI  = str_pad($contoIva, 10);
            $cContoC  = str_pad($contoClienti, 10);

            $output .= "{$tipoRec}{$data}{$numero}{$cliente}{$piva}{$impon}{$iva}{$totale}{$aliquota}{$cContoV}{$cContoI}{$cContoC}\r\n";
        }

        return $output;
    }
}
