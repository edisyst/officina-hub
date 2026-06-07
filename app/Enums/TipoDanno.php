<?php

namespace App\Enums;

enum TipoDanno: string
{
    case Ammaccatura  = 'ammaccatura';
    case Graffi       = 'graffi';
    case Rottura      = 'rottura';
    case Sostituzione = 'sostituzione';
    case Verniciatura = 'verniciatura';
    case Laccatura    = 'laccatura';
    case Stucco       = 'stucco';
    case Altro        = 'altro';

    public function label(): string
    {
        return match($this) {
            self::Ammaccatura  => 'Ammaccatura',
            self::Graffi       => 'Graffi',
            self::Rottura      => 'Rottura',
            self::Sostituzione => 'Sostituzione',
            self::Verniciatura => 'Verniciatura',
            self::Laccatura    => 'Laccatura',
            self::Stucco       => 'Stucco',
            self::Altro        => 'Altro',
        };
    }
}
