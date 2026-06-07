<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lavorazione extends Model
{
    protected $table = 'lavorazioni';

    protected $fillable = [
        'commessa_id',
        'commessa_riga_id',
        'user_id',
        'ponte_id',
        'descrizione',
        'minuti_preventivati',
        'started_at',
        'stopped_at',
        'minuti_effettivi',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'started_at'          => 'datetime',
            'stopped_at'          => 'datetime',
            'minuti_preventivati' => 'integer',
            'minuti_effettivi'    => 'integer',
        ];
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function commessaRiga()
    {
        return $this->belongsTo(CommessaRiga::class);
    }

    public function meccanico()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ponte()
    {
        return $this->belongsTo(Ponte::class);
    }

    /** Lavorazioni avviate ma non ancora fermate */
    public function scopeAttive($query)
    {
        return $query->whereNotNull('started_at')->whereNull('stopped_at');
    }

    public function getIsAttivaAttribute(): bool
    {
        return $this->started_at !== null && $this->stopped_at === null;
    }

    public function getDeltaMinutiAttribute(): ?int
    {
        if ($this->minuti_effettivi === null) {
            return null;
        }
        return $this->minuti_effettivi - $this->minuti_preventivati;
    }
}
