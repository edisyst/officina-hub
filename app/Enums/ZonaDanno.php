<?php

namespace App\Enums;

enum ZonaDanno: string
{
    case AnterioreSx     = 'anteriore_sx';
    case AnterioreDx     = 'anteriore_dx';
    case AnterioreCentro = 'anteriore_centro';
    case LateraleSxAnt   = 'laterale_sx_ant';
    case LateraleSxPost  = 'laterale_sx_post';
    case LateraleDxAnt   = 'laterale_dx_ant';
    case LateraleDxPost  = 'laterale_dx_post';
    case PosterioreSx    = 'posteriore_sx';
    case PosterioreDx    = 'posteriore_dx';
    case PosterioreCentro= 'posteriore_centro';
    case Tetto           = 'tetto';
    case Sottoscocca     = 'sottoscocca';
    case Altro           = 'altro';

    public function label(): string
    {
        return match($this) {
            self::AnterioreSx      => 'Anteriore SX',
            self::AnterioreDx      => 'Anteriore DX',
            self::AnterioreCentro  => 'Anteriore Centro',
            self::LateraleSxAnt    => 'Laterale SX Ant.',
            self::LateraleSxPost   => 'Laterale SX Post.',
            self::LateraleDxAnt    => 'Laterale DX Ant.',
            self::LateraleDxPost   => 'Laterale DX Post.',
            self::PosterioreSx     => 'Posteriore SX',
            self::PosterioreDx     => 'Posteriore DX',
            self::PosterioreCentro => 'Posteriore Centro',
            self::Tetto            => 'Tetto',
            self::Sottoscocca      => 'Sottoscocca',
            self::Altro            => 'Altro',
        };
    }
}
