<?php

namespace App\Services\Export;

use Illuminate\Support\Collection;

/**
 * Tracciato Datagamma XML — STUB non ancora implementato.
 * TODO: implementare secondo le specifiche XML Datagamma
 *       (disponibili nel portale partner Datagamma).
 */
class DatagammaFormatter
{
    public function formatta(Collection $righe): string
    {
        throw new \RuntimeException(
            'Formato Datagamma non ancora implementato. ' .
            'Utilizzare il formato CSV Generico oppure contattare il supporto tecnico.'
        );
    }
}
