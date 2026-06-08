<?php

namespace App\Models;

use App\Enums\StagionePneumatico;
use App\Enums\StatoPneumatico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pneumatico extends Model
{
    use SoftDeletes;

    protected $table = 'pneumatici';

    protected $fillable = [
        'cliente_id', 'veicolo_id', 'stagione', 'marca', 'modello', 'misura',
        'larghezza', 'rapporto', 'diametro', 'indice_carico', 'indice_velocita',
        'numero_pezzi', 'dotati_di_cerchi', 'tipo_cerchi', 'anno_produzione',
        'stato', 'note', 'notifica_inviata_at',
    ];

    protected $casts = [
        'stagione'         => StagionePneumatico::class,
        'stato'            => StatoPneumatico::class,
        'dotati_di_cerchi' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veicolo(): BelongsTo
    {
        return $this->belongsTo(Veicolo::class);
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(DepositoPneumatico::class)->latest();
    }

    public function ultimoMovimento(): HasMany
    {
        return $this->hasMany(DepositoPneumatico::class)->latest()->limit(1);
    }

    /** Codice etichetta es. DEP-2026-00042 */
    public function codiceEtichetta(): string
    {
        $prefisso = setting('etichetta_deposito_prefisso', 'DEP');
        $anno     = $this->created_at ? $this->created_at->format('Y') : now()->format('Y');
        return sprintf('%s-%s-%05d', $prefisso, $anno, $this->id);
    }
}
