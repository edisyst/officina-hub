<?php

namespace App\Models;

use App\Enums\StatoDviIspezione;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DviIspezione extends Model
{
    use SoftDeletes;

    protected $table = 'dvi_ispezioni';

    protected $fillable = [
        'commessa_id', 'user_id', 'stato', 'link_token',
        'link_scade_at', 'inviata_at', 'approvata_at',
        'note_meccanico', 'note_cliente',
    ];

    protected function casts(): array
    {
        return [
            'stato'         => StatoDviIspezione::class,
            'link_scade_at' => 'datetime',
            'inviata_at'    => 'datetime',
            'approvata_at'  => 'datetime',
        ];
    }

    public function commessa(): BelongsTo
    {
        return $this->belongsTo(Commessa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voci(): HasMany
    {
        return $this->hasMany(DviVoce::class)->orderBy('ordinamento');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DviMedia::class);
    }

    public function isTokenValido(): bool
    {
        return $this->link_scade_at !== null
            && $this->link_scade_at->isFuture()
            && $this->stato === StatoDviIspezione::InviataCliente;
    }

    public function calcolaImportoApprovato(): float
    {
        return $this->voci
            ->where('stato_approvazione', \App\Enums\StatoApprovazioneDvi::Approvato->value)
            ->sum('prezzo_stimato');
    }
}
