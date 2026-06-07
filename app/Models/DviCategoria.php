<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DviCategoria extends Model
{
    protected $table = 'dvi_categorie';

    protected $fillable = ['nome', 'icona_css', 'colore_default', 'ordinamento', 'attivo'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    public function scopeAttive($query)
    {
        return $query->where('attivo', true)->orderBy('ordinamento');
    }
}
