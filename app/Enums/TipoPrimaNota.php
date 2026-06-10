<?php

namespace App\Enums;

enum TipoPrimaNota: string
{
    case Entrata = 'entrata';
    case Uscita  = 'uscita';

    public function label(): string
    {
        return match($this) {
            self::Entrata => 'Entrata',
            self::Uscita  => 'Uscita',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Entrata => 'badge-success',
            self::Uscita  => 'badge-danger',
        };
    }
}
