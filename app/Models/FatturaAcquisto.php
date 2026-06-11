<?php

namespace App\Models;

use App\Enums\MetodoPagamentoFornitore;
use App\Enums\StatoFatturaAcquisto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FatturaAcquisto extends Model
{
    use SoftDeletes;

    protected $table = 'fatture_acquisto';

    protected $fillable = [
        'fornitore_id',
        'ddt_fornitore_id',
        'numero_fattura_fornitore',
        'data_fattura',
        'data_ricezione',
        'data_scadenza',
        'imponibile',
        'iva_totale',
        'totale',
        'stato',
        'metodo_pagamento',
        'xml_sdi_path',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'             => StatoFatturaAcquisto::class,
            'metodo_pagamento'  => MetodoPagamentoFornitore::class,
            'data_fattura'      => 'date',
            'data_ricezione'    => 'date',
            'data_scadenza'     => 'date',
            'imponibile'        => 'decimal:2',
            'iva_totale'        => 'decimal:2',
            'totale'            => 'decimal:2',
        ];
    }

    public function fornitore()
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function ddt()
    {
        return $this->belongsTo(DdtFornitore::class, 'ddt_fornitore_id');
    }

    public function righe()
    {
        return $this->hasMany(FatturaAcquistoRiga::class);
    }

    public function pagamenti()
    {
        return $this->hasMany(PagamentoFornitore::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalePagatoAttribute(): float
    {
        return (float) $this->pagamenti->sum('importo');
    }

    public function getSaldoAttribute(): float
    {
        return max(0, (float) $this->totale - $this->totale_pagato);
    }
}
