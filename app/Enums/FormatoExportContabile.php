<?php

namespace App\Enums;

enum FormatoExportContabile: string
{
    case CsvGenerico   = 'csv_generico';
    case PrimaNotaTxt  = 'primanota_txt';
    case TeamSystem    = 'teamsystem';
    case Zucchetti     = 'zucchetti';
    case Datagamma     = 'datagamma';

    public function label(): string
    {
        return match($this) {
            self::CsvGenerico  => 'CSV Generico (Excel)',
            self::PrimaNotaTxt => 'Prima Nota TXT',
            self::TeamSystem   => 'TeamSystem Studio',
            self::Zucchetti    => 'Zucchetti Metodo',
            self::Datagamma    => 'Datagamma XML',
        };
    }
}
