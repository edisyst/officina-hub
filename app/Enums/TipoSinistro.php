<?php

namespace App\Enums;

enum TipoSinistro: string
{
    case RcaDiretta = 'rca_diretta';
    case RcaTerzi   = 'rca_terzi';
    case Kasko      = 'kasko';
    case Cristalli  = 'cristalli';
    case Grandine   = 'grandine';
    case Incendio   = 'incendio';
    case Altro      = 'altro';

    public function label(): string
    {
        return match($this) {
            self::RcaDiretta => 'RCA Diretta',
            self::RcaTerzi   => 'RCA Terzi',
            self::Kasko      => 'Kasko',
            self::Cristalli  => 'Cristalli',
            self::Grandine   => 'Grandine',
            self::Incendio   => 'Incendio',
            self::Altro      => 'Altro',
        };
    }
}
