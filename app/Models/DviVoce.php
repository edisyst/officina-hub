<?php

namespace App\Models;

use App\Enums\StatoApprovazioneDvi;
use App\Enums\UrgenzaDvi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DviVoce extends Model
{
    protected $table = 'dvi_voci';

    protected $fillable = [
        'dvi_ispezione_id', 'categoria', 'descrizione',
        'urgenza', 'prezzo_stimato', 'note', 'ordinamento',
        'stato_approvazione', 'approvato_at',
    ];

    protected function casts(): array
    {
        return [
            'urgenza'            => UrgenzaDvi::class,
            'stato_approvazione' => StatoApprovazioneDvi::class,
            'prezzo_stimato'     => 'decimal:2',
            'approvato_at'       => 'datetime',
        ];
    }

    public function ispezione(): BelongsTo
    {
        return $this->belongsTo(DviIspezione::class, 'dvi_ispezione_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DviMedia::class)->orderBy('created_at');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(DviMedia::class)->where('tipo', 'foto');
    }

    public function video(): HasMany
    {
        return $this->hasMany(DviMedia::class)->where('tipo', 'video');
    }
}
