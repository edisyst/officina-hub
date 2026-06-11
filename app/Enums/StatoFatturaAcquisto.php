<?php

namespace App\Enums;

enum StatoFatturaAcquisto: string
{
    case Ricevuta   = 'ricevuta';
    case Registrata = 'registrata';
    case Pagata     = 'pagata';
    case Contestata = 'contestata';

    public function label(): string
    {
        return match($this) {
            self::Ricevuta   => 'Ricevuta',
            self::Registrata => 'Registrata',
            self::Pagata     => 'Pagata',
            self::Contestata => 'Contestata',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Ricevuta   => 'badge-secondary',
            self::Registrata => 'badge-info',
            self::Pagata     => 'badge-success',
            self::Contestata => 'badge-danger',
        };
    }

    public function accettaPagamenti(): bool
    {
        return in_array($this, [self::Registrata]);
    }
}
