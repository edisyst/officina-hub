<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdiLog extends Model
{
    protected $table = 'sdi_log';

    public $timestamps = false;

    protected $fillable = [
        'documento_id',
        'azione',
        'esito',
        'dettaglio',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }
}
