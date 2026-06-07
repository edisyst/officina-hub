<?php

namespace App\Services;

use App\Models\Documento;
use Illuminate\Support\Facades\DB;

class NumerazioneService
{
    /**
     * Restituisce il prossimo progressivo libero per tipo e anno.
     * Usa lockForUpdate() in transaction per garantire unicità sotto carico concorrente.
     */
    public function prossimo(string $tipo, int $anno): int
    {
        return DB::transaction(function () use ($tipo, $anno) {
            $ultimo = Documento::withTrashed()
                ->where('tipo', $tipo)
                ->where('anno', $anno)
                ->lockForUpdate()
                ->max('progressivo');

            return ($ultimo ?? 0) + 1;
        });
    }

    /** Formatta il numero documento nel formato prefisso-YYYY-NNNN */
    public function formattaNumero(string $tipo, int $anno, int $progressivo): string
    {
        $prefissi = [
            'fattura'      => 'FT',
            'nota_credito' => 'NC',
            'preventivo'   => 'PR',
            'ddt'          => 'DDT',
            'ricevuta'     => 'RC',
        ];

        $prefisso = $prefissi[$tipo] ?? strtoupper($tipo);

        return "{$prefisso}-{$anno}-" . str_pad($progressivo, 4, '0', STR_PAD_LEFT);
    }
}
