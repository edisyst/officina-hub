<?php

namespace App\Models;

use App\Enums\TipoRegistroIva;
use Illuminate\Database\Eloquent\Model;

class RegistroIva extends Model
{
    protected $table = 'registro_iva';

    protected $fillable = [
        'documento_id',
        'fattura_acquisto_id',
        'tipo_registro',
        'data_registrazione',
        'numero_documento',
        'cliente_fornitore',
        'partita_iva',
        'codice_fiscale',
        'imponibile',
        'iva',
        'totale',
        'aliquota_iva',
        'natura_iva',
    ];

    protected function casts(): array
    {
        return [
            'tipo_registro'      => TipoRegistroIva::class,
            'data_registrazione' => 'date',
            'imponibile'         => 'decimal:2',
            'iva'                => 'decimal:2',
            'totale'             => 'decimal:2',
            'aliquota_iva'       => 'decimal:2',
        ];
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }
}
