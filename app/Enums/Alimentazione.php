<?php

namespace App\Enums;

enum Alimentazione: string
{
    case Benzina = 'benzina';
    case Diesel = 'diesel';
    case Ibrido = 'ibrido';
    case Elettrico = 'elettrico';
    case Gpl = 'gpl';
    case Metano = 'metano';

    public function label(): string
    {
        return match($this) {
            self::Benzina => 'Benzina',
            self::Diesel => 'Diesel',
            self::Ibrido => 'Ibrido',
            self::Elettrico => 'Elettrico',
            self::Gpl => 'GPL',
            self::Metano => 'Metano',
        };
    }
}
