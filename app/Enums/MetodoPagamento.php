<?php

namespace App\Enums;

enum MetodoPagamento: string
{
    case Contanti = 'contanti';
    case Bonifico = 'bonifico';
    case Carta    = 'carta';
    case Assegno  = 'assegno';
    case Rid      = 'rid';

    public function label(): string
    {
        return match($this) {
            self::Contanti => 'Contanti',
            self::Bonifico => 'Bonifico bancario',
            self::Carta    => 'Carta di credito/debito',
            self::Assegno  => 'Assegno',
            self::Rid      => 'Addebito diretto (RID/SSD)',
        };
    }

    /** Codice ModalitaPagamento per FatturaPA (tabella AdE) */
    public function codiceModalitaFPA(): string
    {
        return match($this) {
            self::Contanti => 'MP01',
            self::Assegno  => 'MP02',
            self::Bonifico => 'MP05',
            self::Carta    => 'MP08',
            self::Rid      => 'MP19',
        };
    }
}
