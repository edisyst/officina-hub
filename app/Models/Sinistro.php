<?php

namespace App\Models;

use App\Enums\StatoSinistro;
use App\Enums\TipoSinistro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sinistro extends Model
{
    use SoftDeletes;

    protected $table = 'sinistri';

    protected $fillable = [
        'commessa_id',
        'compagnia_assicurativa_id',
        'numero_sinistro',
        'numero_polizza_cliente',
        'numero_polizza_controparte',
        'tipo_sinistro',
        'data_sinistro',
        'luogo_sinistro',
        'descrizione_dinamica',
        'liquidatore_nome',
        'liquidatore_email',
        'liquidatore_telefono',
        'stato',
    ];

    protected function casts(): array
    {
        return [
            'tipo_sinistro' => TipoSinistro::class,
            'stato'         => StatoSinistro::class,
            'data_sinistro' => 'date',
        ];
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function compagniaAssicurativa()
    {
        return $this->belongsTo(CompagniaAssicurativa::class);
    }

    public function perizia()
    {
        return $this->hasOne(Perizia::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class, 'sinistro_id');
    }
}
