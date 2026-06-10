<?php

namespace App\Observers;

use App\Enums\ContoPrimaNota;
use App\Enums\MetodoPrimaNota;
use App\Enums\TipoPrimaNota;
use App\Models\Pagamento;
use App\Models\PrimaNota;

class PagamentoObserver
{
    /** Crea automaticamente un record prima nota quando viene registrato un pagamento */
    public function created(Pagamento $pagamento): void
    {
        $metodo = MetodoPrimaNota::daMetodoPagamento($pagamento->metodo);
        $conto  = ContoPrimaNota::daMetodo($metodo);

        $documento = $pagamento->documento;
        $causale   = $documento
            ? "Incasso {$documento->numero} - {$documento->cliente->nome_completo}"
            : "Pagamento registrato";

        PrimaNota::create([
            'data'        => $pagamento->data_pagamento,
            'causale'     => $causale,
            'tipo'        => TipoPrimaNota::Entrata,
            'importo'     => $pagamento->importo,
            'metodo'      => $metodo,
            'conto'       => $conto,
            'documento_id' => $pagamento->documento_id,
            'pagamento_id' => $pagamento->id,
            'note'        => $pagamento->riferimento ?: null,
            'automatico'  => true,
            'user_id'     => $pagamento->user_id,
        ]);
    }
}
