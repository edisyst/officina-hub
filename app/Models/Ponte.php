<?php

namespace App\Models;

use App\Enums\TipoPonte;
use Illuminate\Database\Eloquent\Model;

class Ponte extends Model
{
    protected $table = 'ponti';

    protected $fillable = [
        'nome',
        'tipo',
        'descrizione',
        'attivo',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'tipo'       => TipoPonte::class,
            'attivo'     => 'boolean',
            'ordinamento' => 'integer',
        ];
    }

    public function appuntamenti()
    {
        return $this->hasMany(Appuntamento::class);
    }

    public function lavorazioni()
    {
        return $this->hasMany(Lavorazione::class);
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true)->orderBy('ordinamento');
    }
}
