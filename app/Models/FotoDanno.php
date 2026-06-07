<?php

namespace App\Models;

use App\Enums\FaseFoto;
use Illuminate\Database\Eloquent\Model;

class FotoDanno extends Model
{
    protected $table = 'foto_danni';

    protected $fillable = [
        'danno_veicolo_id',
        'commessa_id',
        'percorso',
        'nome_file',
        'mime_type',
        'dimensione_bytes',
        'fase',
        'descrizione',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fase' => FaseFoto::class,
        ];
    }

    public function danno()
    {
        return $this->belongsTo(DannoVeicolo::class, 'danno_veicolo_id');
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
