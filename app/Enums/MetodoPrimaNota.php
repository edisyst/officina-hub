<?php

namespace App\Enums;

enum MetodoPrimaNota: string
{
    case Contanti = 'contanti';
    case Bonifico = 'bonifico';
    case Carta    = 'carta';
    case Assegno  = 'assegno';
    case Rid      = 'rid';
    case Altro    = 'altro';

    public function label(): string
    {
        return match($this) {
            self::Contanti => 'Contanti',
            self::Bonifico => 'Bonifico',
            self::Carta    => 'Carta/POS',
            self::Assegno  => 'Assegno',
            self::Rid      => 'RID/SSD',
            self::Altro    => 'Altro',
        };
    }

    /** Mappa dal MetodoPagamento fattura al metodo prima nota */
    public static function daMetodoPagamento(MetodoPagamento $metodo): self
    {
        return match($metodo) {
            MetodoPagamento::Contanti => self::Contanti,
            MetodoPagamento::Bonifico => self::Bonifico,
            MetodoPagamento::Carta    => self::Carta,
            MetodoPagamento::Assegno  => self::Assegno,
            MetodoPagamento::Rid      => self::Rid,
        };
    }
}
