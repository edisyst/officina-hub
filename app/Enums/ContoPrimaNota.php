<?php

namespace App\Enums;

enum ContoPrimaNota: string
{
    case Cassa = 'cassa';
    case Banca = 'banca';
    case Pos   = 'pos';

    public function label(): string
    {
        return match($this) {
            self::Cassa => 'Cassa',
            self::Banca => 'Banca',
            self::Pos   => 'POS',
        };
    }

    /** Determina il conto in base al metodo di pagamento */
    public static function daMetodo(MetodoPrimaNota $metodo): self
    {
        return match($metodo) {
            MetodoPrimaNota::Contanti => self::Cassa,
            MetodoPrimaNota::Carta    => self::Pos,
            default                   => self::Banca,
        };
    }
}
