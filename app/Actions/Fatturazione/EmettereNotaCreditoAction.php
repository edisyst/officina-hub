<?php

namespace App\Actions\Fatturazione;

use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Models\Documento;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;

class EmettereNotaCreditoAction
{
    public function __construct(
        private readonly NumerazioneService $numerazione,
    ) {}

    /**
     * Emette una nota di credito che annulla la fattura originale.
     * Le righe vengono copiate con quantità negative (TipoDocumento TD04).
     */
    public function execute(Documento $fattura): Documento
    {
        $fattura->load(['cliente', 'righe']);

        return DB::transaction(function () use ($fattura) {
            $anno = now()->year;
            $tipo = TipoDocumento::NotaCredito->value;

            $progressivo = $this->numerazione->prossimo($tipo, $anno);
            $numero      = $this->numerazione->formattaNumero($tipo, $anno, $progressivo);

            $notaCredito = Documento::create([
                'tipo'             => TipoDocumento::NotaCredito,
                'numero'           => $numero,
                'anno'             => $anno,
                'progressivo'      => $progressivo,
                'commessa_id'      => $fattura->commessa_id,
                'cliente_id'       => $fattura->cliente_id,
                'data_emissione'   => now()->toDateString(),
                'data_scadenza'    => null,
                'imponibile'       => -(float) $fattura->imponibile,
                'iva_totale'       => -(float) $fattura->iva_totale,
                'totale'           => -(float) $fattura->totale,
                'stato'            => StatoDocumento::Bozza,
                'metodo_pagamento' => $fattura->metodo_pagamento,
                'note'             => "Nota di credito per annullamento fattura {$fattura->numero}",
            ]);

            // Copia le righe con importi negativi
            foreach ($fattura->righe as $idx => $riga) {
                $notaCredito->righe()->create([
                    'commessa_riga_id'   => $riga->commessa_riga_id,
                    'descrizione'        => $riga->descrizione,
                    'unita_misura'       => $riga->unita_misura,
                    'quantita'           => -(float) $riga->quantita,
                    'prezzo_unitario'    => $riga->prezzo_unitario,
                    'sconto_percentuale' => $riga->sconto_percentuale,
                    'iva_percentuale'    => $riga->iva_percentuale,
                    'natura_iva'         => $riga->natura_iva,
                    'imponibile_riga'    => -(float) $riga->imponibile_riga,
                    'iva_riga'           => -(float) $riga->iva_riga,
                    'ordinamento'        => $idx,
                ]);
            }

            // Annulla la fattura originale
            $fattura->update(['stato' => StatoDocumento::Annullata]);

            return $notaCredito;
        });
    }
}
