<?php

namespace App\Enums;

enum UnitaMisura: string
{
    case Pezzo = 'pz';
    case Litro = 'lt';
    case Chilogrammo = 'kg';
    case Millilitro = 'ml';
    case Grammo = 'gr';
    case Metro = 'mt';

    public function label(): string
    {
        return match($this) {
            self::Pezzo => 'Pz',
            self::Litro => 'Lt',
            self::Chilogrammo => 'Kg',
            self::Millilitro => 'ml',
            self::Grammo => 'gr',
            self::Metro => 'mt',
        };
    }
}
