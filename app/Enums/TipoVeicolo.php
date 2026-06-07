<?php

namespace App\Enums;

enum TipoVeicolo: string
{
    case Auto    = 'auto';
    case Moto    = 'moto';
    case Furgone = 'furgone';

    public function label(): string
    {
        return match($this) {
            self::Auto    => 'Auto',
            self::Moto    => 'Moto',
            self::Furgone => 'Furgone',
        };
    }
}
