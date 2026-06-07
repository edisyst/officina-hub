<?php

namespace App\Actions\Fatturazione;

use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Models\Commessa;
use App\Models\Documento;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;

class GeneraFatturaAction
{
    public function __construct(
        private readonly NumerazioneService $numerazione,
    ) {}

    /**
     * Genera una fattura in stato bozza a partire da una commessa completata/consegnata.
     * Copia le righe, calcola i totali, porta la commessa allo stato "fatturata".
     */
    public function execute(Commessa $commessa): Documento
    {
        $commessa->load(['cliente', 'righe']);

        return DB::transaction(function () use ($commessa) {
            $anno = now()->year;
            $tipo = TipoDocumento::Fattura->value;

            $progressivo = $this->numerazione->prossimo($tipo, $anno);
            $numero      = $this->numerazione->formattaNumero($tipo, $anno, $progressivo);

            // Calcola i totali dalle righe della commessa
            [$imponibile, $ivaTotale] = $this->calcolaTotali($commessa);

            $documento = Documento::create([
                'tipo'             => TipoDocumento::Fattura,
                'numero'           => $numero,
                'anno'             => $anno,
                'progressivo'      => $progressivo,
                'commessa_id'      => $commessa->id,
                'cliente_id'       => $commessa->cliente_id,
                'data_emissione'   => now()->toDateString(),
                'data_scadenza'    => now()->addDays(30)->toDateString(),
                'imponibile'       => round($imponibile, 2),
                'iva_totale'       => round($ivaTotale, 2),
                'totale'           => round($imponibile + $ivaTotale, 2),
                'stato'            => StatoDocumento::Bozza,
                'metodo_pagamento' => null,
            ]);

            // Copia righe dalla commessa nel documento
            foreach ($commessa->righe as $idx => $riga) {
                $imp = (float) $riga->quantita * (float) $riga->prezzo_unitario;
                $imp = $imp * (1 - (float) $riga->sconto_percentuale / 100);
                $iva = $imp * ((float) $riga->iva_percentuale / 100);

                $documento->righe()->create([
                    'commessa_riga_id'   => $riga->id,
                    'descrizione'        => $riga->descrizione,
                    'unita_misura'       => 'pz',
                    'quantita'           => $riga->quantita,
                    'prezzo_unitario'    => $riga->prezzo_unitario,
                    'sconto_percentuale' => $riga->sconto_percentuale,
                    'iva_percentuale'    => $riga->iva_percentuale,
                    'imponibile_riga'    => round($imp, 2),
                    'iva_riga'           => round($iva, 2),
                    'ordinamento'        => $idx,
                ]);
            }

            // Porta la commessa allo stato "fatturata"
            $commessa->update(['stato' => StatoCommessa::Fatturata]);

            return $documento;
        });
    }

    private function calcolaTotali(Commessa $commessa): array
    {
        $imponibile = 0.0;
        $ivaTotale  = 0.0;

        foreach ($commessa->righe as $riga) {
            $imp = (float) $riga->quantita * (float) $riga->prezzo_unitario;
            $imp = $imp * (1 - (float) $riga->sconto_percentuale / 100);
            $imponibile += $imp;
            $ivaTotale  += $imp * ((float) $riga->iva_percentuale / 100);
        }

        return [$imponibile, $ivaTotale];
    }
}
