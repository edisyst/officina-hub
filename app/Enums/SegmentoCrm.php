<?php

namespace App\Enums;

enum SegmentoCrm: string
{
    case Nuovo     = 'nuovo';
    case Attivo    = 'attivo';
    case ARischio  = 'a_rischio';
    case Perso     = 'perso';
    case Vip       = 'vip';

    public function label(): string
    {
        return match($this) {
            self::Nuovo    => 'Nuovo',
            self::Attivo   => 'Attivo',
            self::ARischio => 'A rischio',
            self::Perso    => 'Perso',
            self::Vip      => 'VIP',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Nuovo    => 'badge-info',
            self::Attivo   => 'badge-success',
            self::ARischio => 'badge-warning',
            self::Perso    => 'badge-danger',
            self::Vip      => 'badge-primary',
        };
    }

    public function colore(): string
    {
        return match($this) {
            self::Nuovo    => '#17a2b8',
            self::Attivo   => '#28a745',
            self::ARischio => '#ffc107',
            self::Perso    => '#dc3545',
            self::Vip      => '#007bff',
        };
    }
}
