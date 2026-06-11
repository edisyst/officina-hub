<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DdtFornitoreRiga extends Model
{
    protected $table = 'ddt_fornitore_righe';

    protected $fillable = [
        'ddt_fornitore_id',
        'ordine_riga_id',
        'articolo_id',
        'descrizione',
        'quantita_ricevuta',
        'prezzo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'prezzo_unitario' => 'decimal:2',
        ];
    }

    public function ddt()
    {
        return $this->belongsTo(DdtFornitore::class, 'ddt_fornitore_id');
    }

    public function ordineRiga()
    {
        return $this->belongsTo(OrdineFornitoreRiga::class, 'ordine_riga_id');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }
}
