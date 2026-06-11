<?php

namespace App\Observers;

use App\Enums\ContoPrimaNota;
use App\Enums\MetodoPrimaNota;
use App\Enums\TipoPrimaNota;
use App\Models\PagamentoFornitore;
use App\Models\PrimaNota;

class PagamentoFornitoreObserver
{
    public function created(PagamentoFornitore $pagamento): void
    {
        $metodo = $pagamento->metodo->aMetodoPrimaNota();
        $conto  = ContoPrimaNota::daMetodo($metodo);

        $fattura  = $pagamento->fattura;
        $causale  = $fattura
            ? "Pagamento {$fattura->numero_fattura_fornitore} - {$fattura->fornitore->ragione_sociale}"
            : "Pagamento fornitore registrato";

        PrimaNota::create([
            'data'                   => $pagamento->data_pagamento,
            'causale'                => $causale,
            'tipo'                   => TipoPrimaNota::Uscita,
            'importo'                => $pagamento->importo,
            'metodo'                 => $metodo,
            'conto'                  => $conto,
            'pagamento_fornitore_id' => $pagamento->id,
            'fornitore_id'           => $fattura?->fornitore_id,
            'note'                   => $pagamento->riferimento ?: null,
            'automatico'             => true,
            'user_id'                => $pagamento->user_id,
        ]);
    }
}
