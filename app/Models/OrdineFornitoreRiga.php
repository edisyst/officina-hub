<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdineFornitoreRiga extends Model
{
    protected $table = 'ordine_fornitore_righe';

    protected $fillable = [
        'ordine_fornitore_id',
        'articolo_id',
        'descrizione',
        'codice_fornitore',
        'quantita_ordinata',
        'quantita_ricevuta',
        'prezzo_unitario_atteso',
    ];

    protected function casts(): array
    {
        return [
            'prezzo_unitario_atteso' => 'decimal:2',
        ];
    }

    public function ordine()
    {
        return $this->belongsTo(OrdineFornitore::class, 'ordine_fornitore_id');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }

    public function quantitaDaRicevere(): int
    {
        return max(0, $this->quantita_ordinata - $this->quantita_ricevuta);
    }
}
