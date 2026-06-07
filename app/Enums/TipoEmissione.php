<?php

namespace App\Enums;

enum TipoEmissione: string
{
    case Cliente       = 'cliente';
    case Assicurazione = 'assicurazione';
    case Entrambi      = 'entrambi';

    public function label(): string
    {
        return match($this) {
            self::Cliente       => 'Cliente',
            self::Assicurazione => 'Assicurazione',
            self::Entrambi      => 'Entrambi',
        };
    }
}
