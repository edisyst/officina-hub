<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DdtFornitore extends Model
{
    protected $table = 'ddt_fornitori';

    protected $fillable = [
        'ordine_fornitore_id',
        'fornitore_id',
        'numero_ddt',
        'data_ddt',
        'data_ricezione',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data_ddt'       => 'date',
            'data_ricezione' => 'date',
        ];
    }

    public function ordine()
    {
        return $this->belongsTo(OrdineFornitore::class, 'ordine_fornitore_id');
    }

    public function fornitore()
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function righe()
    {
        return $this->hasMany(DdtFornitoreRiga::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fatturaAcquisto()
    {
        return $this->hasOne(FatturaAcquisto::class);
    }
}
