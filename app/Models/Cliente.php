<?php

namespace App\Models;

use App\Enums\SegmentoCrm;
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
        'data_nascita',
        'professione',
        'come_ci_ha_conosciuto',
        'consenso_marketing',
        'consenso_marketing_at',
        'valore_lifetime',
        'numero_visite',
        'ultima_visita_at',
        'segmento_crm',
    ];

    protected function casts(): array
    {
        return [
            'tipo'                  => TipoCliente::class,
            'patente_scadenza'      => 'date',
            'data_nascita'          => 'date',
            'consenso_marketing'    => 'boolean',
            'consenso_marketing_at' => 'datetime',
            'ultima_visita_at'      => 'datetime',
            'segmento_crm'          => SegmentoCrm::class,
            'valore_lifetime'       => 'decimal:2',
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

    public function crmNote()
    {
        return $this->hasMany(CrmNota::class)->latest('data_interazione');
    }

    public function campagnaInvii()
    {
        return $this->hasMany(CampagnaInvio::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
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
