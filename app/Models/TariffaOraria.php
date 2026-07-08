<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TariffaOraria extends Model
{
    use HasFactory;

    protected $table = 'tariffe_orarie';

    protected $fillable = ['nome', 'tariffa_oraria', 'is_default', 'is_attiva'];

    protected function casts(): array
    {
        return [
            'tariffa_oraria' => 'decimal:2',
            'is_default'     => 'boolean',
            'is_attiva'      => 'boolean',
        ];
    }

    public function scopeAttive($query)
    {
        return $query->where('is_attiva', true);
    }
}
