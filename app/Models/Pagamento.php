<?php

namespace App\Models;

use App\Enums\MetodoPagamento;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'pagamenti';

    protected $fillable = [
        'documento_id',
        'data_pagamento',
        'importo',
        'metodo',
        'riferimento',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'metodo'         => MetodoPagamento::class,
            'data_pagamento' => 'date',
            'importo'        => 'decimal:2',
        ];
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
