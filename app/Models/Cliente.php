<?php

namespace App\Models;

use App\Enums\TipoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clienti';

    protected $fillable = [
        'tipo',
        'nome',
        'cognome',
        'ragione_sociale',
        'codice_fiscale',
        'partita_iva',
        'codice_destinatario_sdi',
        'pec_sdi',
        'email',
        'telefono',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'note',
        'patente_numero',
        'patente_scadenza',
    ];

    protected function casts(): array
    {
        return [
            'tipo'             => TipoCliente::class,
            'patente_scadenza' => 'date',
        ];
    }

    public function veicoli()
    {
        return $this->belongsToMany(Veicolo::class, 'cliente_veicolo')
            ->withPivot(['proprietario_attuale', 'data_inizio', 'data_fine'])
            ->withTimestamps();
    }

    public function commesse()
    {
        return $this->hasMany(Commessa::class);
    }

    /** Nome visualizzabile in base al tipo di cliente */
    public function getNomeCompletoAttribute(): string
    {
        if ($this->tipo === TipoCliente::Giuridica) {
            return $this->ragione_sociale ?? '';
        }

        return trim("{$this->nome} {$this->cognome}");
    }

    /** Scope per la ricerca live */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nome', 'like', "%{$term}%")
              ->orWhere('cognome', 'like', "%{$term}%")
              ->orWhere('ragione_sociale', 'like', "%{$term}%")
              ->orWhere('codice_fiscale', 'like', "%{$term}%")
              ->orWhere('partita_iva', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('telefono', 'like', "%{$term}%");
        });
    }
}
