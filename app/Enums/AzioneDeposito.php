<?php

namespace App\Enums;

enum AzioneDeposito: string
{
    case Deposito         = 'deposito';
    case Ritiro           = 'ritiro';
    case Smaltimento      = 'smaltimento';
    case CambioStagionale = 'cambio_stagionale';

    public function label(): string
    {
        return match($this) {
            self::Deposito         => 'Deposito',
            self::Ritiro           => 'Ritiro',
            self::Smaltimento      => 'Smaltimento',
            self::CambioStagionale => 'Cambio stagionale',
        };
    }
}
