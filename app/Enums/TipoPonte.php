<?php

namespace App\Enums;

enum TipoPonte: string
{
    case Meccanica   = 'meccanica';
    case Carrozzeria = 'carrozzeria';
    case Diagnosi    = 'diagnosi';

    public function label(): string
    {
        return match($this) {
            self::Meccanica   => 'Meccanica',
            self::Carrozzeria => 'Carrozzeria',
            self::Diagnosi    => 'Diagnosi',
        };
    }
}
