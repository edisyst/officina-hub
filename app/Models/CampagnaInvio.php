<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampagnaInvio extends Model
{
    protected $table = 'campagna_invii';

    protected $fillable = [
        'campagna_email_id',
        'cliente_id',
        'stato',
        'inviata_at',
        'errore',
    ];

    protected function casts(): array
    {
        return [
            'inviata_at' => 'datetime',
        ];
    }

    public function campagna()
    {
        return $this->belongsTo(CampagnaEmail::class, 'campagna_email_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
