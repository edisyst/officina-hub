<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allegato extends Model
{
    protected $table = 'allegati';

    protected $fillable = [
        'commessa_id',
        'nome_file',
        'percorso',
        'mime_type',
        'dimensione_bytes',
        'descrizione',
        'user_id',
    ];

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isImmagine(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
