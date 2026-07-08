<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatricePrezzoScaglione extends Model
{
    use HasFactory;

    protected $table = 'matrici_prezzo_scaglioni';

    protected $fillable = [
        'matrice_prezzo_id',
        'costo_da',
        'costo_a',
        'markup_percent',
        'arrotondamento',
    ];

    protected function casts(): array
    {
        return [
            'costo_da'      => 'decimal:2',
            'costo_a'       => 'decimal:2',
            'markup_percent' => 'decimal:2',
        ];
    }

    public function matrice(): BelongsTo
    {
        return $this->belongsTo(MatricePrezzo::class, 'matrice_prezzo_id');
    }
}
