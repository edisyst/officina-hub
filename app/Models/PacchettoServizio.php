<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PacchettoServizio extends Model
{
    use SoftDeletes;

    protected $table = 'pacchetti_servizio';

    protected $fillable = [
        'nome',
        'descrizione',
        'tipo_commessa',
        'tipo_veicolo',
        'alimentazione',
        'prezzo_totale_suggerito',
        'note',
        'attivo',
        'utilizzi',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'prezzo_totale_suggerito' => 'decimal:2',
            'attivo'                  => 'boolean',
        ];
    }

    public function righe()
    {
        return $this->hasMany(PacchettoRiga::class)->orderBy('ordinamento');
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nome', 'like', "%{$term}%")
              ->orWhere('descrizione', 'like', "%{$term}%");
        });
    }

    public function calcolaTotale(): float
    {
        return $this->righe->where('tipo', '!=', 'nota')->sum(function ($riga) {
            $imponibile = (float) $riga->quantita * (float) $riga->prezzo_unitario
                * (1 - (float) $riga->sconto_percentuale / 100);
            return $imponibile * (1 + (float) $riga->iva_percentuale / 100);
        });
    }

    public function isCompatibile(Commessa $commessa): bool
    {
        if ($this->tipo_commessa !== 'entrambi' && $this->tipo_commessa !== $commessa->tipo->value) {
            return false;
        }

        if (! $commessa->veicolo) {
            return true;
        }

        if ($this->tipo_veicolo !== 'entrambi' && $this->tipo_veicolo !== $commessa->veicolo->tipo->value) {
            return false;
        }

        if ($this->alimentazione !== 'tutte' && $this->alimentazione !== $commessa->veicolo->alimentazione?->value) {
            return false;
        }

        return true;
    }
}
