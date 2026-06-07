<?php

namespace App\Observers;

use App\Enums\StatoDocumento;
use App\Enums\TipoRegistroIva;
use App\Models\Documento;
use App\Models\RegistroIva;
use App\Services\Analytics\KpiService;

class DocumentoObserver
{
    /** Aggiorna il registro IVA al cambio di stato */
    public function updated(Documento $documento): void
    {
        if (!$documento->wasChanged('stato')) {
            return;
        }

        // Invalida cache KPI fatturato quando cambia lo stato del documento
        KpiService::invalidaCache();

        // Crea le voci nel registro IVA quando il documento viene emesso
        if ($documento->stato === StatoDocumento::Emessa) {
            $this->popolaRegistroIva($documento);
        }

        // Rimuovi le voci se il documento viene annullato
        if ($documento->stato === StatoDocumento::Annullata) {
            RegistroIva::where('documento_id', $documento->id)->delete();
        }
    }

    private function popolaRegistroIva(Documento $documento): void
    {
        $documento->load(['cliente', 'righe']);

        // Rimuovi eventuali voci precedenti (rigenera sempre da zero)
        RegistroIva::where('documento_id', $documento->id)->delete();

        // Raggruppa per aliquota o natura IVA
        $gruppi = $documento->righe->groupBy(function ($riga) {
            return $riga->natura_iva ?: (string)(float) $riga->iva_percentuale;
        });

        foreach ($gruppi as $chiave => $righe) {
            $imponibile = $righe->sum(fn($r) => (float) $r->imponibile_riga);
            $iva        = $righe->sum(fn($r) => (float) $r->iva_riga);
            $aliquota   = is_numeric($chiave) ? (float) $chiave : 0.0;
            $natura     = is_numeric($chiave) ? null : $chiave;

            RegistroIva::create([
                'documento_id'      => $documento->id,
                'tipo_registro'     => TipoRegistroIva::Vendite,
                'data_registrazione' => $documento->data_emissione,
                'numero_documento'  => $documento->numero,
                'cliente_fornitore' => $documento->cliente->nome_completo,
                'partita_iva'       => $documento->cliente->partita_iva,
                'codice_fiscale'    => $documento->cliente->codice_fiscale,
                'imponibile'        => round($imponibile, 2),
                'iva'               => round($iva, 2),
                'totale'            => round($imponibile + $iva, 2),
                'aliquota_iva'      => $aliquota,
                'natura_iva'        => $natura,
            ]);
        }
    }
}
