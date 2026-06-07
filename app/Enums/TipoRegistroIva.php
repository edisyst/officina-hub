<?php

namespace App\Enums;

enum TipoRegistroIva: string
{
    case Vendite  = 'vendite';
    case Acquisti = 'acquisti';

    public function label(): string
    {
        return match($this) {
            self::Vendite  => 'Registro IVA Vendite',
            self::Acquisti => 'Registro IVA Acquisti',
        };
    }
}
