<?php

namespace App\Enums;

enum TipoMovimento: string
{
    case Carico = 'carico';
    case Scarico = 'scarico';
    case Rettifica = 'rettifica';
    case ResoFornitore = 'reso_fornitore';
    case ResoCliente = 'reso_cliente';

    public function label(): string
    {
        return match($this) {
            self::Carico => 'Carico',
            self::Scarico => 'Scarico',
            self::Rettifica => 'Rettifica',
            self::ResoFornitore => 'Reso fornitore',
            self::ResoCliente => 'Reso cliente',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Carico => 'badge-success',
            self::Scarico => 'badge-danger',
            self::Rettifica => 'badge-warning',
            self::ResoFornitore => 'badge-info',
            self::ResoCliente => 'badge-secondary',
        };
    }

    /** Restituisce true se il tipo aumenta la giacenza */
    public function aumentaGiacenza(): bool
    {
        return match($this) {
            self::Carico, self::Rettifica, self::ResoFornitore => true,
            self::Scarico, self::ResoCliente => false,
        };
    }
}
