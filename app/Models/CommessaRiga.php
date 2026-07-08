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
        'tariffa_oraria_id',
        'pacchetto_servizio_id',
        'descrizione',
        'quantita',
        'prezzo_unitario',
        'prezzo_acquisto',
        'sconto_percentuale',
        'iva_percentuale',
        'ore_preventivate',
        'ordinamento',
        'in_garanzia',
        'garanzia_id',
        'casa_madre_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo'               => TipoRiga::class,
            'ore_preventivate'   => 'decimal:2',
            'quantita'           => 'decimal:2',
            'prezzo_unitario'    => 'decimal:2',
            'prezzo_acquisto'    => 'decimal:2',
            'sconto_percentuale' => 'decimal:2',
            'iva_percentuale'    => 'decimal:2',
            'in_garanzia'        => 'boolean',
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

    public function tariffaOraria()
    {
        return $this->belongsTo(TariffaOraria::class, 'tariffa_oraria_id');
    }

    public function garanzia()
    {
        return $this->belongsTo(Garanzia::class);
    }

    public function casaMadre()
    {
        return $this->belongsTo(CasaMadre::class);
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

    /** Totale a carico del cliente: zero se la riga è in garanzia */
    public function getTotaleClienteAttribute(): float
    {
        return $this->in_garanzia ? 0.0 : $this->totale;
    }

    /** Totale a carico della casa madre: prezzo pieno se riga in garanzia */
    public function getTotaleCasaMadreAttribute(): float
    {
        return $this->in_garanzia ? $this->totale : 0.0;
    }
}
