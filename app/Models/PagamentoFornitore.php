<?php

namespace App\Models;

use App\Enums\MetodoPagamentoFornitore;
use Illuminate\Database\Eloquent\Model;

class PagamentoFornitore extends Model
{
    protected $table = 'pagamenti_fornitori';

    protected $fillable = [
        'fattura_acquisto_id',
        'data_pagamento',
        'importo',
        'metodo',
        'riferimento',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'metodo'          => MetodoPagamentoFornitore::class,
            'data_pagamento'  => 'date',
            'importo'         => 'decimal:2',
        ];
    }

    public function fattura()
    {
        return $this->belongsTo(FatturaAcquisto::class, 'fattura_acquisto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
