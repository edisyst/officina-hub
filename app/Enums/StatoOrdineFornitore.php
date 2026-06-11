<?php

namespace App\Enums;

enum StatoOrdineFornitore: string
{
    case Bozza                 = 'bozza';
    case Inviato               = 'inviato';
    case Confermato            = 'confermato';
    case ParzialmenteRicevuto  = 'parzialmente_ricevuto';
    case Ricevuto              = 'ricevuto';
    case Annullato             = 'annullato';

    public function label(): string
    {
        return match($this) {
            self::Bozza                => 'Bozza',
            self::Inviato              => 'Inviato',
            self::Confermato           => 'Confermato',
            self::ParzialmenteRicevuto => 'Parz. ricevuto',
            self::Ricevuto             => 'Ricevuto',
            self::Annullato            => 'Annullato',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Bozza                => 'badge-secondary',
            self::Inviato              => 'badge-info',
            self::Confermato           => 'badge-primary',
            self::ParzialmenteRicevuto => 'badge-warning',
            self::Ricevuto             => 'badge-success',
            self::Annullato            => 'badge-danger',
        };
    }

    public function modificabile(): bool
    {
        return $this === self::Bozza;
    }
}
