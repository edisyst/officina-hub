<?php

namespace App\Enums;

enum StatoSinistro: string
{
    case Aperto          = 'aperto';
    case InPerizia       = 'in_perizia';
    case PeriziaRicevuta = 'perizia_ricevuta';
    case InLavorazione   = 'in_lavorazione';
    case Chiuso          = 'chiuso';
    case Contestato      = 'contestato';

    public function label(): string
    {
        return match($this) {
            self::Aperto          => 'Aperto',
            self::InPerizia       => 'In Perizia',
            self::PeriziaRicevuta => 'Perizia Ricevuta',
            self::InLavorazione   => 'In Lavorazione',
            self::Chiuso          => 'Chiuso',
            self::Contestato      => 'Contestato',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Aperto          => 'badge-secondary',
            self::InPerizia       => 'badge-warning',
            self::PeriziaRicevuta => 'badge-info',
            self::InLavorazione   => 'badge-primary',
            self::Chiuso          => 'badge-success',
            self::Contestato      => 'badge-danger',
        };
    }
}
