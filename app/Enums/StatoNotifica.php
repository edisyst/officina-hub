<?php

namespace App\Enums;

enum StatoNotifica: string
{
    case InCoda    = 'in_coda';
    case Inviata   = 'inviata';
    case Fallita   = 'fallita';
    case Rimbalzata = 'rimbalzata';

    public function label(): string
    {
        return match($this) {
            self::InCoda     => 'In coda',
            self::Inviata    => 'Inviata',
            self::Fallita    => 'Fallita',
            self::Rimbalzata => 'Rimbalzata',
        };
    }
}
