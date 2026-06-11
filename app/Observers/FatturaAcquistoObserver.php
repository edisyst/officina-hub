<?php

namespace App\Observers;

use App\Enums\StatoFatturaAcquisto;
use App\Enums\TipoRegistroIva;
use App\Models\FatturaAcquisto;
use App\Models\RegistroIva;

class FatturaAcquistoObserver
{
    public function updated(FatturaAcquisto $fattura): void
    {
        if (
            $fattura->wasChanged('stato') &&
            $fattura->stato === StatoFatturaAcquisto::Registrata
        ) {
            $this->creaRegistroIva($fattura);
        }
    }

    private function creaRegistroIva(FatturaAcquisto $fattura): void
    {
        if (RegistroIva::where('fattura_acquisto_id', $fattura->id)->exists()) {
            return;
        }

        $fornitore = $fattura->fornitore;

        RegistroIva::create([
            'fattura_acquisto_id' => $fattura->id,
            'tipo_registro'       => TipoRegistroIva::Acquisti,
            'data_registrazione'  => $fattura->data_fattura,
            'numero_documento'    => $fattura->numero_fattura_fornitore,
            'cliente_fornitore'   => $fornitore->ragione_sociale,
            'partita_iva'         => $fornitore->partita_iva,
            'codice_fiscale'      => $fornitore->codice_fiscale,
            'imponibile'          => $fattura->imponibile,
            'iva'                 => $fattura->iva_totale,
            'totale'              => $fattura->totale,
            'aliquota_iva'        => $fattura->imponibile > 0
                ? round(($fattura->iva_totale / $fattura->imponibile) * 100, 2)
                : 22.00,
            'natura_iva'          => null,
        ]);
    }
}
