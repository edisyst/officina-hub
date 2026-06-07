<?php

namespace App\Models;

use App\Enums\TipoDanno;
use App\Enums\ZonaDanno;
use Illuminate\Database\Eloquent\Model;

class DannoVeicolo extends Model
{
    protected $table = 'danni_veicolo';

    protected $fillable = [
        'commessa_id',
        'zona',
        'tipo_danno',
        'descrizione',
        'quantita',
        'prezzo_stimato',
        'prezzo_perizia',
        'incluso_in_perizia',
        'note',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'zona'              => ZonaDanno::class,
            'tipo_danno'        => TipoDanno::class,
            'quantita'          => 'decimal:2',
            'prezzo_stimato'    => 'decimal:2',
            'prezzo_perizia'    => 'decimal:2',
            'incluso_in_perizia'=> 'boolean',
        ];
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function foto()
    {
        return $this->hasMany(FotoDanno::class);
    }
}
