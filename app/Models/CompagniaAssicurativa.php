<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompagniaAssicurativa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compagnie_assicurative';

    protected $fillable = [
        'nome', 'codice_abi', 'email', 'pec',
        'telefono', 'indirizzo', 'referente', 'note',
    ];

    public function sinistri()
    {
        return $this->hasMany(Sinistro::class);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nome', 'like', "%{$term}%")
              ->orWhere('codice_abi', 'like', "%{$term}%");
        });
    }
}
