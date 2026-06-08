<?php

namespace App\Enums;

enum StagionePneumatico: string
{
    case Estivo           = 'estivo';
    case Invernale        = 'invernale';
    case QuattroStagioni  = 'quattro_stagioni';

    public function label(): string
    {
        return match($this) {
            self::Estivo          => 'Estivo',
            self::Invernale       => 'Invernale',
            self::QuattroStagioni => '4 Stagioni',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Estivo          => 'badge-warning',
            self::Invernale       => 'badge-info',
            self::QuattroStagioni => 'badge-secondary',
        };
    }

    public function opposta(): self
    {
        return match($this) {
            self::Estivo    => self::Invernale,
            self::Invernale => self::Estivo,
            default         => self::Estivo,
        };
    }
}
