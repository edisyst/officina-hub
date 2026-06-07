<?php

namespace App\Enums;

enum StatoDviIspezione: string
{
    case Bozza               = 'bozza';
    case InviataCliente      = 'inviata_cliente';
    case Approvata           = 'approvata';
    case ParzialmenteApprovata = 'parzialmente_approvata';
    case Rifiutata           = 'rifiutata';

    public function label(): string
    {
        return match($this) {
            self::Bozza                => 'Bozza',
            self::InviataCliente       => 'Inviata al cliente',
            self::Approvata            => 'Approvata',
            self::ParzialmenteApprovata => 'Parz. approvata',
            self::Rifiutata            => 'Rifiutata',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Bozza                => 'badge-secondary',
            self::InviataCliente       => 'badge-warning',
            self::Approvata            => 'badge-success',
            self::ParzialmenteApprovata => 'badge-info',
            self::Rifiutata            => 'badge-danger',
        };
    }
}
