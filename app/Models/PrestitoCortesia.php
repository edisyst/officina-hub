<?php

namespace App\Models;

use App\Enums\StatoPrestito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestitoCortesia extends Model
{
    protected $table = 'prestiti_cortesia';

    protected $fillable = [
        'veicolo_cortesia_id', 'commessa_id', 'cliente_id',
        'user_id_consegna', 'user_id_rientro',
        'data_consegna', 'data_rientro_prevista', 'data_rientro_effettiva',
        'km_consegna', 'km_rientro',
        'carburante_consegna', 'carburante_rientro',
        'cauzione_importo', 'cauzione_pagata',
        'firma_consegna_svg', 'firma_rientro_svg',
        'note_consegna', 'note_rientro', 'stato',
    ];

    protected $casts = [
        'stato'                    => StatoPrestito::class,
        'data_consegna'            => 'datetime',
        'data_rientro_prevista'    => 'date',
        'data_rientro_effettiva'   => 'datetime',
        'cauzione_pagata'          => 'boolean',
        'cauzione_importo'         => 'decimal:2',
    ];

    public function veicolo(): BelongsTo
    {
        return $this->belongsTo(VeicoloCortesia::class, 'veicolo_cortesia_id');
    }

    public function commessa(): BelongsTo
    {
        return $this->belongsTo(Commessa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function utenteConsegna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_consegna');
    }

    public function utenteRientro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_rientro');
    }

    public function getKmPercorsiAttribute(): ?int
    {
        if ($this->km_rientro !== null) {
            return $this->km_rientro - $this->km_consegna;
        }
        return null;
    }

    public function getDeltaCarburanteAttribute(): ?int
    {
        if ($this->carburante_rientro !== null) {
            return $this->carburante_rientro - $this->carburante_consegna;
        }
        return null;
    }

    public function isInRitardo(): bool
    {
        return $this->stato === StatoPrestito::InCorso
            && $this->data_rientro_prevista->isPast();
    }

    public function scopeInRitardo($query)
    {
        return $query->where('stato', StatoPrestito::InCorso)
            ->where('data_rientro_prevista', '<', now()->toDateString());
    }
}
