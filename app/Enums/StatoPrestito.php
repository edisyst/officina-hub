<?php

namespace App\Enums;

enum StatoPrestito: string
{
    case Prenotato  = 'prenotato';
    case InCorso    = 'in_corso';
    case Rientrato  = 'rientrato';
    case Annullato  = 'annullato';

    public function label(): string
    {
        return match($this) {
            self::Prenotato  => 'Prenotato',
            self::InCorso    => 'In corso',
            self::Rientrato  => 'Rientrato',
            self::Annullato  => 'Annullato',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Prenotato  => 'info',
            self::InCorso    => 'primary',
            self::Rientrato  => 'success',
            self::Annullato  => 'secondary',
        };
    }

    public function colorCalendario(): string
    {
        return match($this) {
            self::Prenotato  => '#17a2b8',
            self::InCorso    => '#007bff',
            self::Rientrato  => '#28a745',
            self::Annullato  => '#6c757d',
        };
    }
}
