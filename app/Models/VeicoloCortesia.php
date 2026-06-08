<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VeicoloCortesia extends Model
{
    use SoftDeletes;

    protected $table = 'veicoli_cortesia';

    protected $fillable = [
        'targa', 'marca', 'modello', 'anno', 'colore', 'tipo',
        'km_attuali', 'carburante_tipo', 'livello_carburante_inizio',
        'note', 'attivo', 'immagine_path',
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'anno'   => 'integer',
    ];

    public function prestiti(): HasMany
    {
        return $this->hasMany(PrestitoCortesia::class);
    }

    public function prestitiAttivi(): HasMany
    {
        return $this->hasMany(PrestitoCortesia::class)
            ->whereIn('stato', ['prenotato', 'in_corso']);
    }

    /** Verifica se il veicolo è disponibile in un intervallo date */
    public function isDisponibile(\DateTimeInterface $dal, \DateTimeInterface $al, ?int $escludiId = null): bool
    {
        $query = $this->prestiti()
            ->whereIn('stato', ['prenotato', 'in_corso'])
            ->where('data_consegna', '<', $al)
            ->where('data_rientro_prevista', '>=', $dal->format('Y-m-d'));

        if ($escludiId) {
            $query->where('id', '!=', $escludiId);
        }

        return $query->doesntExist();
    }

    /** Targa + marca/modello per select */
    public function getDescrizioneAttribute(): string
    {
        return "{$this->targa} — {$this->marca} {$this->modello}";
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function iconaTipo(): string
    {
        return match($this->tipo) {
            'moto'    => 'fas fa-motorcycle',
            'furgone' => 'fas fa-truck',
            default   => 'fas fa-car',
        };
    }
}
