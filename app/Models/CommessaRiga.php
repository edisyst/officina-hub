<?php

namespace App\Models;

use App\Enums\TipoRiga;
use Illuminate\Database\Eloquent\Model;

class CommessaRiga extends Model
{
    protected $table = 'commessa_righe';

    protected $fillable = [
        'commessa_id',
        'tipo',
        'articolo_id',
        'tariffa_manodopera_id',
        'pacchetto_servizio_id',
        'descrizione',
        'quantita',
        'prezzo_unitario',
        'prezzo_acquisto',
        'sconto_percentuale',
        'iva_percentuale',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'tipo'               => TipoRiga::class,
            'quantita'           => 'decimal:2',
            'prezzo_unitario'    => 'decimal:2',
            'prezzo_acquisto'    => 'decimal:2',
            'sconto_percentuale' => 'decimal:2',
            'iva_percentuale'    => 'decimal:2',
        ];
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }

    public function tariffa()
    {
        return $this->belongsTo(TariffaManodopera::class, 'tariffa_manodopera_id');
    }

    public function pacchetto()
    {
        return $this->belongsTo(PacchettoServizio::class, 'pacchetto_servizio_id');
    }

    public function getImponibileAttribute(): float
    {
        $importo = (float) $this->quantita * (float) $this->prezzo_unitario;
        return $importo * (1 - (float) $this->sconto_percentuale / 100);
    }

    public function getIvaAttribute(): float
    {
        return $this->imponibile * ((float) $this->iva_percentuale / 100);
    }

    public function getTotaleAttribute(): float
    {
        return $this->imponibile + $this->iva;
    }
}
