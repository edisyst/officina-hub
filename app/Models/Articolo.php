<?php

namespace App\Models;

use App\Enums\UnitaMisura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Articolo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'articoli';

    protected $fillable = [
        'codice',
        'codice_fornitore',
        'descrizione',
        'descrizione_estesa',
        'categoria_articolo_id',
        'fornitore_id',
        'unita_misura',
        'prezzo_acquisto',
        'prezzo_vendita',
        'iva_percentuale',
        'scorta_minima',
        'scorta_massima',
        'giacenza_attuale',
        'ubicazione',
        'attivo',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'unita_misura'    => UnitaMisura::class,
            'prezzo_acquisto' => 'decimal:2',
            'prezzo_vendita'  => 'decimal:2',
            'iva_percentuale' => 'decimal:2',
            'attivo'          => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaArticolo::class, 'categoria_articolo_id');
    }

    public function fornitore()
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function movimenti()
    {
        return $this->hasMany(MovimentoMagazzino::class)->latest('created_at');
    }

    public function isSottoScorta(): bool
    {
        return $this->scorta_minima > 0 && $this->giacenza_attuale <= $this->scorta_minima;
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeSottoScorta($query)
    {
        return $query->where('scorta_minima', '>', 0)
                     ->whereColumn('giacenza_attuale', '<=', 'scorta_minima');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('codice', 'like', "%{$term}%")
              ->orWhere('descrizione', 'like', "%{$term}%")
              ->orWhere('codice_fornitore', 'like', "%{$term}%");
        });
    }
}
