<?php

namespace App\Models;

use App\Enums\TipoGaranzia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garanzia extends Model
{
    use SoftDeletes;

    protected $table = 'garanzie';

    protected $fillable = [
        'veicolo_id',
        'casa_madre_id',
        'tipo',
        'descrizione',
        'data_inizio',
        'data_fine',
        'km_inizio',
        'km_fine',
        'numero_pratica',
        'note',
        'attiva',
    ];

    protected function casts(): array
    {
        return [
            'tipo'        => TipoGaranzia::class,
            'data_inizio' => 'date',
            'data_fine'   => 'date',
            'attiva'      => 'boolean',
        ];
    }

    public function veicolo()
    {
        return $this->belongsTo(Veicolo::class);
    }

    public function casaMadre()
    {
        return $this->belongsTo(CasaMadre::class);
    }

    public function commessaRighe()
    {
        return $this->hasMany(CommessaRiga::class);
    }

    public function isScaduta(): bool
    {
        return $this->data_fine !== null && $this->data_fine->isPast();
    }

    public function isInScadenza(): bool
    {
        if ($this->data_fine === null || $this->isScaduta()) return false;
        return $this->data_fine->lte(now()->addDays(30));
    }

    public function badgeClass(): string
    {
        if ($this->isScaduta()) return 'badge-secondary';
        if ($this->isInScadenza()) return 'badge-warning';
        return 'badge-success';
    }

    public function badgeLabel(): string
    {
        if ($this->isScaduta()) return 'Scaduta';
        if ($this->isInScadenza()) return 'In scadenza';
        return 'Attiva';
    }

    public function scopeAttive($query)
    {
        return $query->where('attiva', true)
            ->where(fn($q) => $q->whereNull('data_fine')->orWhere('data_fine', '>=', now()->toDateString()));
    }

    public function scopePerVeicolo($query, int $veicoloId)
    {
        return $query->where('veicolo_id', $veicoloId);
    }
}
