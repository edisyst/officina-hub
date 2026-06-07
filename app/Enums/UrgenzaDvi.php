<?php

namespace App\Enums;

enum UrgenzaDvi: string
{
    case Ok         = 'ok';
    case Attenzione = 'attenzione';
    case Urgente    = 'urgente';

    public function label(): string
    {
        return match($this) {
            self::Ok         => 'OK',
            self::Attenzione => 'Attenzione',
            self::Urgente    => 'Urgente',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Ok         => 'badge-success',
            self::Attenzione => 'badge-warning',
            self::Urgente    => 'badge-danger',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::Ok         => 'success',
            self::Attenzione => 'warning',
            self::Urgente    => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Ok         => 'fas fa-check-circle',
            self::Attenzione => 'fas fa-exclamation-triangle',
            self::Urgente    => 'fas fa-times-circle',
        };
    }
}
