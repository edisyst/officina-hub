<?php

namespace App\Enums;

enum TipoCommessa: string
{
    case Meccanica   = 'meccanica';
    case Carrozzeria = 'carrozzeria';
    case Tagliando   = 'tagliando';

    public function label(): string
    {
        return match($this) {
            self::Meccanica   => 'Meccanica',
            self::Carrozzeria => 'Carrozzeria',
            self::Tagliando   => 'Tagliando',
        };
    }
}
