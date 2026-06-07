<?php

namespace App\Models;

use App\Enums\TipoScadenza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scadenza extends Model
{
    use SoftDeletes;

    protected $table = 'scadenze';

    protected $fillable = [
        'veicolo_id',
        'cliente_id',
        'tipo',
        'descrizione',
        'data_scadenza',
        'km_scadenza',
        'km_attuali_al_momento',
        'notifica_giorni_prima',
        'notifica_inviata_at',
        'notifica_disabilitata',
        'commessa_origine_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo'                   => TipoScadenza::class,
            'data_scadenza'          => 'date',
            'notifica_inviata_at'    => 'datetime',
            'notifica_disabilitata'  => 'boolean',
        ];
    }

    public function veicolo()
    {
        return $this->belongsTo(Veicolo::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function commessaOrigine()
    {
        return $this->belongsTo(Commessa::class, 'commessa_origine_id');
    }

    public function notificheLog()
    {
        return $this->hasMany(NotificaLog::class);
    }

    /** Restituisce il numero di giorni alla scadenza (negativo se scaduta) */
    public function getGiorniAllaScadenzaAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->data_scadenza, false);
    }

    /** Classe CSS del colore urgenza */
    public function getColoreUrgenzaAttribute(): string
    {
        $giorni = $this->giorni_alla_scadenza;

        if ($giorni < 0)  return 'danger';
        if ($giorni <= 14) return 'warning';
        if ($giorni <= 60) return 'info';
        return 'success';
    }

    /** Scadenze attive che richiedono notifica entro i giorni configurati */
    public function scopeDaNotificare($query)
    {
        return $query
            ->where('notifica_disabilitata', false)
            ->where('data_scadenza', '>=', now()->startOfDay())
            ->where('data_scadenza', '<=', now()->startOfDay()->addDays(
                // usa il campo della singola scadenza
                \Illuminate\Database\Eloquent\Builder::raw('notifica_giorni_prima')
            ));
    }
}
