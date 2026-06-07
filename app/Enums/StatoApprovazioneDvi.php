<?php

namespace App\Enums;

enum StatoApprovazioneDvi: string
{
    case InAttesa  = 'in_attesa';
    case Approvato = 'approvato';
    case Rimandato = 'rimandato';

    public function label(): string
    {
        return match($this) {
            self::InAttesa  => 'In attesa',
            self::Approvato => 'Approvato',
            self::Rimandato => 'Rimandato',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::InAttesa  => 'badge-secondary',
            self::Approvato => 'badge-success',
            self::Rimandato => 'badge-warning',
        };
    }
}
