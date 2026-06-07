<?php

namespace App\Models;

use App\Enums\StatoAppuntamento;
use Illuminate\Database\Eloquent\Model;

class Appuntamento extends Model
{
    protected $table = 'appuntamenti';

    protected $fillable = [
        'commessa_id',
        'cliente_id',
        'veicolo_id',
        'ponte_id',
        'user_id',
        'titolo',
        'note',
        'data_ora_inizio',
        'data_ora_fine',
        'tutto_il_giorno',
        'stato',
    ];

    protected function casts(): array
    {
        return [
            'stato'           => StatoAppuntamento::class,
            'data_ora_inizio' => 'datetime',
            'data_ora_fine'   => 'datetime',
            'tutto_il_giorno' => 'boolean',
        ];
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veicolo()
    {
        return $this->belongsTo(Veicolo::class);
    }

    public function ponte()
    {
        return $this->belongsTo(Ponte::class);
    }

    public function meccanico()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Titolo per il calendario: include targa e cliente se disponibili */
    public function getTitoloCalendarioAttribute(): string
    {
        $parti = [$this->titolo];
        if ($this->cliente) {
            $parti[] = $this->cliente->nome_completo;
        }
        if ($this->veicolo?->targa) {
            $parti[] = "({$this->veicolo->targa})";
        }
        return implode(' — ', $parti);
    }
}
