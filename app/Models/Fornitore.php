<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornitore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fornitori';

    protected $fillable = [
        'ragione_sociale',
        'partita_iva',
        'codice_fiscale',
        'email',
        'telefono',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'note',
    ];

    public function articoli()
    {
        return $this->hasMany(Articolo::class);
    }

    public function movimentiCarico()
    {
        return $this->hasManyThrough(MovimentoMagazzino::class, Articolo::class, 'fornitore_id', 'articolo_id')
            ->where('tipo', 'carico')
            ->latest('movimenti_magazzino.created_at');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ragione_sociale', 'like', "%{$term}%")
              ->orWhere('partita_iva', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
