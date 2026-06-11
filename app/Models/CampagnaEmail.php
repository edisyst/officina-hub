<?php

namespace App\Models;

use App\Enums\StatoCampagna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagnaEmail extends Model
{
    use SoftDeletes;

    protected $table = 'campagne_email';

    protected $fillable = [
        'nome',
        'oggetto',
        'corpo',
        'stato',
        'segmento_target',
        'filtro_json',
        'pianificata_at',
        'inviata_at',
        'totale_destinatari',
        'totale_inviati',
        'totale_errori',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'          => StatoCampagna::class,
            'filtro_json'    => 'array',
            'pianificata_at' => 'datetime',
            'inviata_at'     => 'datetime',
        ];
    }

    public function invii()
    {
        return $this->hasMany(CampagnaInvio::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBozza($query)
    {
        return $query->where('stato', StatoCampagna::Bozza);
    }

    public function scopePianificata($query)
    {
        return $query->where('stato', StatoCampagna::Pianificata);
    }
}
