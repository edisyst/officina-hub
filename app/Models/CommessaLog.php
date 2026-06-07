<?php

namespace App\Models;

use App\Enums\StatoCommessa;
use Illuminate\Database\Eloquent\Model;

class CommessaLog extends Model
{
    public $timestamps = false;

    protected $table = 'commessa_log';

    protected $fillable = [
        'commessa_id',
        'stato_da',
        'stato_a',
        'user_id',
        'nota',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stato_da' => StatoCommessa::class,
            'stato_a' => StatoCommessa::class,
            'created_at' => 'datetime',
        ];
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
