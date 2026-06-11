<?php

namespace App\Enums;

enum MetodoPagamentoFornitore: string
{
    case Contanti = 'contanti';
    case Bonifico = 'bonifico';
    case Carta    = 'carta';
    case Assegno  = 'assegno';
    case Rid      = 'rid';
    case Riba     = 'riba';

    public function label(): string
    {
        return match($this) {
            self::Contanti => 'Contanti',
            self::Bonifico => 'Bonifico',
            self::Carta    => 'Carta/POS',
            self::Assegno  => 'Assegno',
            self::Rid      => 'RID/SSD',
            self::Riba     => 'RiBa',
        };
    }

    public function aMetodoPrimaNota(): MetodoPrimaNota
    {
        return match($this) {
            self::Contanti => MetodoPrimaNota::Contanti,
            self::Bonifico => MetodoPrimaNota::Bonifico,
            self::Carta    => MetodoPrimaNota::Carta,
            self::Assegno  => MetodoPrimaNota::Assegno,
            self::Rid      => MetodoPrimaNota::Rid,
            self::Riba     => MetodoPrimaNota::Altro,
        };
    }
}
