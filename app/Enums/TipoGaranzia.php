<?php

namespace App\Enums;

enum TipoGaranzia: string
{
    case GaranziaCostruttore = 'garanzia_costruttore';
    case GaranziaUsato       = 'garanzia_usato';
    case GaranziaRiparazione = 'garanzia_riparazione';
    case GaranziaRicambio    = 'garanzia_ricambio';
    case Convenzione         = 'convenzione';

    public function label(): string
    {
        return match($this) {
            self::GaranziaCostruttore => 'Garanzia costruttore',
            self::GaranziaUsato       => 'Garanzia usato',
            self::GaranziaRiparazione => 'Garanzia riparazione',
            self::GaranziaRicambio    => 'Garanzia ricambio',
            self::Convenzione         => 'Convenzione',
        };
    }

    /** Questi tipi richiedono una casa madre per la fatturazione */
    public function richiedeCasaMadre(): bool
    {
        return in_array($this, [self::GaranziaCostruttore, self::Convenzione]);
    }
}
