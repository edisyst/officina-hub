<?php

namespace App\Enums;

enum StatoCampagna: string
{
    case Bozza      = 'bozza';
    case Pianificata = 'pianificata';
    case InInvio    = 'in_invio';
    case Completata = 'completata';
    case Annullata  = 'annullata';

    public function label(): string
    {
        return match($this) {
            self::Bozza      => 'Bozza',
            self::Pianificata => 'Pianificata',
            self::InInvio    => 'In invio',
            self::Completata => 'Completata',
            self::Annullata  => 'Annullata',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Bozza      => 'badge-secondary',
            self::Pianificata => 'badge-info',
            self::InInvio    => 'badge-warning',
            self::Completata => 'badge-success',
            self::Annullata  => 'badge-danger',
        };
    }
}
