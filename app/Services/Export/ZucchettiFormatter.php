<?php

namespace App\Services\Export;

use Illuminate\Support\Collection;

/**
 * Tracciato Zucchetti Metodo — STUB non ancora implementato.
 * TODO: implementare secondo le specifiche ufficiali di interfaccia Zucchetti Metodo
 *       (richiedere il documento "Interfaccia importazione movimenti contabili" a Zucchetti).
 */
class ZucchettiFormatter
{
    public function formatta(Collection $righe): string
    {
        throw new \RuntimeException(
            'Formato Zucchetti non ancora implementato. ' .
            'Utilizzare il formato CSV Generico oppure contattare il supporto tecnico.'
        );
    }
}
