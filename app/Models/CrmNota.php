<?php

namespace App\Models;

use App\Enums\TipoCrmNota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmNota extends Model
{
    use SoftDeletes;

    protected $table = 'crm_note';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'testo',
        'tipo',
        'data_interazione',
    ];

    protected function casts(): array
    {
        return [
            'tipo'             => TipoCrmNota::class,
            'data_interazione' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
