<?php

namespace App\Actions\Commessa;

use App\Models\Commessa;

class GeneraNumeroProgressivoAction
{
    public function execute(): string
    {
        $anno = now()->year;
        $prefisso = "COM-{$anno}-";

        // Recupera l'ultimo numero progressivo dell'anno corrente con lock
        $ultimo = Commessa::withTrashed()
            ->where('numero', 'like', "{$prefisso}%")
            ->lockForUpdate()
            ->orderByDesc('numero')
            ->value('numero');

        if ($ultimo) {
            $progressivo = (int) substr($ultimo, -4) + 1;
        } else {
            $progressivo = 1;
        }

        return $prefisso . str_pad($progressivo, 4, '0', STR_PAD_LEFT);
    }
}
