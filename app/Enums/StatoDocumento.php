<?php

namespace App\Enums;

enum StatoDocumento: string
{
    case Bozza       = 'bozza';
    case Emessa      = 'emessa';
    case InviataSdi  = 'inviata_sdi';
    case AccettataSdi = 'accettata_sdi';
    case ScartatasSdi = 'scartata_sdi';
    case Pagata      = 'pagata';
    case Annullata   = 'annullata';

    public function label(): string
    {
        return match($this) {
            self::Bozza        => 'Bozza',
            self::Emessa       => 'Emessa',
            self::InviataSdi   => 'Inviata SdI',
            self::AccettataSdi => 'Accettata SdI',
            self::ScartatasSdi => 'Scartata SdI',
            self::Pagata       => 'Pagata',
            self::Annullata    => 'Annullata',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Bozza        => 'badge-secondary',
            self::Emessa       => 'badge-primary',
            self::InviataSdi   => 'badge-info',
            self::AccettataSdi => 'badge-success',
            self::ScartatasSdi => 'badge-danger',
            self::Pagata       => 'badge-success',
            self::Annullata    => 'badge-dark',
        };
    }

    /** Il documento non è più modificabile (solo bozza è modificabile) */
    public function isImmutabile(): bool
    {
        return $this !== self::Bozza;
    }

    /** Verifica se il documento può ricevere pagamenti */
    public function accettaPagamenti(): bool
    {
        return in_array($this, [self::Emessa, self::InviataSdi, self::AccettataSdi]);
    }
}
