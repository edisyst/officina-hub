<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacchettoRiga extends Model
{
    protected $table = 'pacchetto_righe';

    protected $fillable = [
        'pacchetto_servizio_id',
        'tipo',
        'descrizione',
        'articolo_id',
        'quantita',
        'prezzo_unitario',
        'sconto_percentuale',
        'iva_percentuale',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'quantita'           => 'decimal:2',
            'prezzo_unitario'    => 'decimal:2',
            'sconto_percentuale' => 'decimal:2',
            'iva_percentuale'    => 'decimal:2',
        ];
    }

    public function pacchetto()
    {
        return $this->belongsTo(PacchettoServizio::class, 'pacchetto_servizio_id');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }

    public function getImponibileAttribute(): float
    {
        if ($this->tipo === 'nota') {
            return 0;
        }
        $importo = (float) $this->quantita * (float) $this->prezzo_unitario;
        return $importo * (1 - (float) $this->sconto_percentuale / 100);
    }

    public function getTotaleAttribute(): float
    {
        return $this->imponibile * (1 + (float) $this->iva_percentuale / 100);
    }
}
