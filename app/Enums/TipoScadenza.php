<?php

namespace App\Enums;

enum TipoScadenza: string
{
    case Revisione    = 'revisione';
    case Tagliando    = 'tagliando';
    case Assicurazione = 'assicurazione';
    case Bollo        = 'bollo';
    case Altro        = 'altro';

    public function label(): string
    {
        return match($this) {
            self::Revisione     => 'Revisione',
            self::Tagliando     => 'Tagliando',
            self::Assicurazione => 'Assicurazione',
            self::Bollo         => 'Bollo',
            self::Altro         => 'Altro',
        };
    }
}
