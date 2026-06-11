<?php

namespace App\Enums;

enum TipoCrmNota: string
{
    case Nota         = 'nota';
    case Chiamata     = 'chiamata';
    case Email        = 'email';
    case Appuntamento = 'appuntamento';
    case Altro        = 'altro';

    public function label(): string
    {
        return match($this) {
            self::Nota         => 'Nota',
            self::Chiamata     => 'Chiamata',
            self::Email        => 'Email',
            self::Appuntamento => 'Appuntamento',
            self::Altro        => 'Altro',
        };
    }

    public function icona(): string
    {
        return match($this) {
            self::Nota         => 'fas fa-sticky-note',
            self::Chiamata     => 'fas fa-phone',
            self::Email        => 'fas fa-envelope',
            self::Appuntamento => 'fas fa-calendar',
            self::Altro        => 'fas fa-circle',
        };
    }
}
