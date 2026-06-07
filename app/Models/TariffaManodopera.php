<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TariffaManodopera extends Model
{
    use SoftDeletes;

    protected $table = 'tariffe_manodopera';

    protected $fillable = [
        'codice',
        'descrizione',
        'categoria',
        'minuti_standard',
        'prezzo_listino',
        'iva_percentuale',
        'tipo_veicolo',
        'attivo',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'prezzo_listino'  => 'decimal:2',
            'iva_percentuale' => 'decimal:2',
            'attivo'          => 'boolean',
        ];
    }

    public function getOreStandardAttribute(): float
    {
        return round($this->minuti_standard / 60, 2);
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('codice', 'like', "%{$term}%")
              ->orWhere('descrizione', 'like', "%{$term}%")
              ->orWhere('categoria', 'like', "%{$term}%");
        });
    }
}
