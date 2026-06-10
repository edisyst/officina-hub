<?php

namespace App\Models;

use App\Enums\ContoPrimaNota;
use App\Enums\MetodoPrimaNota;
use App\Enums\TipoPrimaNota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrimaNota extends Model
{
    use SoftDeletes;

    protected $table = 'prima_nota';

    protected $fillable = [
        'data',
        'causale',
        'tipo',
        'importo',
        'metodo',
        'conto',
        'documento_id',
        'pagamento_id',
        'fornitore_id',
        'note',
        'automatico',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'date',
            'importo'    => 'decimal:2',
            'tipo'       => TipoPrimaNota::class,
            'metodo'     => MetodoPrimaNota::class,
            'conto'      => ContoPrimaNota::class,
            'automatico' => 'boolean',
        ];
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function pagamento()
    {
        return $this->belongsTo(Pagamento::class);
    }

    public function fornitore()
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEntrate($query)
    {
        return $query->where('tipo', TipoPrimaNota::Entrata);
    }

    public function scopeUscite($query)
    {
        return $query->where('tipo', TipoPrimaNota::Uscita);
    }
}
