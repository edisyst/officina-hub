<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaArticolo extends Model
{
    protected $table = 'categorie_articoli';

    protected $fillable = [
        'nome',
        'descrizione',
        'parent_id',
        'ordinamento',
    ];

    public function parent()
    {
        return $this->belongsTo(CategoriaArticolo::class, 'parent_id');
    }

    public function figli()
    {
        return $this->hasMany(CategoriaArticolo::class, 'parent_id')->orderBy('ordinamento');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'categoria_articolo_id');
    }

    public function scopeRadici($query)
    {
        return $query->whereNull('parent_id')->orderBy('ordinamento');
    }
}
