<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case Preventivo = 'preventivo';
    case Ddt = 'ddt';
    case Fattura = 'fattura';
    case NotaCredito = 'nota_credito';
    case Ricevuta = 'ricevuta';

    public function label(): string
    {
        return match($this) {
            self::Preventivo => 'Preventivo',
            self::Ddt => 'DDT',
            self::Fattura => 'Fattura',
            self::NotaCredito => 'Nota di credito',
            self::Ricevuta => 'Ricevuta',
        };
    }

    public function prefisso(): string
    {
        return match($this) {
            self::Preventivo => 'PR',
            self::Ddt => 'DDT',
            self::Fattura => 'FT',
            self::NotaCredito => 'NC',
            self::Ricevuta => 'RC',
        };
    }

    /** Codice TipoDocumento per il blocco FatturaPA DatiGeneraliDocumento */
    public function codiceFatturaPA(): string
    {
        return match($this) {
            self::Fattura    => 'TD01',
            self::NotaCredito => 'TD04',
            self::Ricevuta   => 'TD06',
            default          => 'TD01',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Preventivo  => 'badge-secondary',
            self::Ddt         => 'badge-info',
            self::Fattura     => 'badge-primary',
            self::NotaCredito => 'badge-warning',
            self::Ricevuta    => 'badge-success',
        };
    }
}
