<?php

namespace App\Enums;

enum StatoPneumatico: string
{
    case Montato          = 'montato';
    case InDeposito       = 'in_deposito';
    case Smaltito         = 'smaltito';
    case RitiratoCLiente  = 'ritirato_cliente';

    public function label(): string
    {
        return match($this) {
            self::Montato         => 'Montato',
            self::InDeposito      => 'In deposito',
            self::Smaltito        => 'Smaltito',
            self::RitiratoCLiente => 'Ritirato dal cliente',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Montato         => 'badge-success',
            self::InDeposito      => 'badge-primary',
            self::Smaltito        => 'badge-secondary',
            self::RitiratoCLiente => 'badge-dark',
        };
    }
}
