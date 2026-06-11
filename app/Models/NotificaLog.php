<?php

namespace App\Models;

use App\Enums\StatoNotifica;
use Illuminate\Database\Eloquent\Model;

class NotificaLog extends Model
{
    protected $table = 'notifiche_log';

    protected $fillable = [
        'tipo',
        'sottotipo',
        'campagna_email_id',
        'destinatario',
        'oggetto',
        'corpo',
        'stato',
        'errore',
        'scadenza_id',
        'commessa_id',
        'cliente_id',
        'tentativi',
        'inviata_at',
        'pneumatico_id',
    ];

    protected function casts(): array
    {
        return [
            'stato'      => StatoNotifica::class,
            'inviata_at' => 'datetime',
        ];
    }

    public function scadenza()
    {
        return $this->belongsTo(Scadenza::class);
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
