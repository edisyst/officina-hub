<?php

namespace App\Models;

use App\Enums\StatoOrdineFornitore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdineFornitore extends Model
{
    use SoftDeletes;

    protected $table = 'ordini_fornitori';

    protected $fillable = [
        'numero',
        'anno',
        'progressivo',
        'fornitore_id',
        'stato',
        'data_ordine',
        'data_consegna_prevista',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'                  => StatoOrdineFornitore::class,
            'data_ordine'            => 'date',
            'data_consegna_prevista' => 'date',
        ];
    }

    public function fornitore()
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function righe()
    {
        return $this->hasMany(OrdineFornitoreRiga::class);
    }

    public function ddt()
    {
        return $this->hasMany(DdtFornitore::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('numero', 'like', "%{$search}%")
              ->orWhereHas('fornitore', fn($f) => $f->where('ragione_sociale', 'like', "%{$search}%"));
        });
    }

    public function isCompletamenteRicevuto(): bool
    {
        return $this->righe->every(fn($r) => $r->quantita_ricevuta >= $r->quantita_ordinata);
    }
}
