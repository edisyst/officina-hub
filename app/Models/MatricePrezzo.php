<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatricePrezzo extends Model
{
    use HasFactory;

    protected $table = 'matrici_prezzo';

    protected $fillable = ['nome', 'is_default', 'is_attiva'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_attiva'  => 'boolean',
        ];
    }

    public function scaglioni(): HasMany
    {
        return $this->hasMany(MatricePrezzoScaglione::class)->orderBy('costo_da');
    }

    public function scopeAttive($query)
    {
        return $query->where('is_attiva', true);
    }
}
