<?php

namespace App\Enums;

enum FaseFoto: string
{
    case Ingresso      = 'ingresso';
    case Lavorazione   = 'lavorazione';
    case Completamento = 'completamento';

    public function label(): string
    {
        return match($this) {
            self::Ingresso      => 'Ingresso',
            self::Lavorazione   => 'Lavorazione',
            self::Completamento => 'Completamento',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Ingresso      => 'badge-secondary',
            self::Lavorazione   => 'badge-warning',
            self::Completamento => 'badge-success',
        };
    }
}
