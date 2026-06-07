<?php

namespace App\Enums;

enum StatoAppuntamento: string
{
    case Pianificato = 'pianificato';
    case Confermato  = 'confermato';
    case InCorso     = 'in_corso';
    case Completato  = 'completato';
    case Annullato   = 'annullato';

    public function label(): string
    {
        return match($this) {
            self::Pianificato => 'Pianificato',
            self::Confermato  => 'Confermato',
            self::InCorso     => 'In Corso',
            self::Completato  => 'Completato',
            self::Annullato   => 'Annullato',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pianificato => 'badge-secondary',
            self::Confermato  => 'badge-info',
            self::InCorso     => 'badge-primary',
            self::Completato  => 'badge-success',
            self::Annullato   => 'badge-danger',
        };
    }

    public function colorCalendario(): string
    {
        return match($this) {
            self::Pianificato => '#6c757d',
            self::Confermato  => '#17a2b8',
            self::InCorso     => '#007bff',
            self::Completato  => '#28a745',
            self::Annullato   => '#dc3545',
        };
    }
}
