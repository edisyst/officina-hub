<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatturaAcquistoRiga extends Model
{
    protected $table = 'fattura_acquisto_righe';

    protected $fillable = [
        'fattura_acquisto_id',
        'articolo_id',
        'descrizione',
        'quantita',
        'prezzo_unitario',
        'iva_percentuale',
        'imponibile_riga',
    ];

    protected function casts(): array
    {
        return [
            'quantita'       => 'decimal:2',
            'prezzo_unitario' => 'decimal:2',
            'iva_percentuale' => 'decimal:2',
            'imponibile_riga' => 'decimal:2',
        ];
    }

    public function fattura()
    {
        return $this->belongsTo(FatturaAcquisto::class, 'fattura_acquisto_id');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }
}
