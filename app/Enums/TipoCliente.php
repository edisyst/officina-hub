<?php

namespace App\Enums;

enum TipoCliente: string
{
    case Fisica = 'fisica';
    case Giuridica = 'giuridica';

    public function label(): string
    {
        return match($this) {
            self::Fisica => 'Persona Fisica',
            self::Giuridica => 'Persona Giuridica',
        };
    }
}
