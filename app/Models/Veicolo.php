<?php

namespace App\Models;

use App\Enums\Alimentazione;
use App\Enums\TipoVeicolo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veicolo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'veicoli';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'targa',
        'vin',
        'marca',
        'modello',
        'versione',
        'alimentazione',
        'cilindrata',
        'anno_immatricolazione',
        'colore',
        'km_attuali',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoVeicolo::class,
            'alimentazione' => Alimentazione::class,
        ];
    }

    public function clienti()
    {
        return $this->belongsToMany(Cliente::class, 'cliente_veicolo')
            ->withPivot(['proprietario_attuale', 'data_inizio', 'data_fine'])
            ->withTimestamps();
    }

    public function clientePrincipale()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function commesse()
    {
        return $this->hasMany(Commessa::class);
    }

    /** Descrizione completa del veicolo */
    public function getDescrizioneAttribute(): string
    {
        return trim("{$this->marca} {$this->modello}" . ($this->versione ? " {$this->versione}" : ''));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('targa', 'like', "%{$term}%")
              ->orWhere('vin', 'like', "%{$term}%")
              ->orWhere('marca', 'like', "%{$term}%")
              ->orWhere('modello', 'like', "%{$term}%");
        });
    }
}
