<?php

namespace App\Enums;

enum TipoRiga: string
{
    case Manodopera = 'manodopera';
    case Articolo = 'articolo';
    case Nota = 'nota';

    public function label(): string
    {
        return match($this) {
            self::Manodopera => 'Manodopera',
            self::Articolo => 'Articolo',
            self::Nota => 'Nota',
        };
    }
}
