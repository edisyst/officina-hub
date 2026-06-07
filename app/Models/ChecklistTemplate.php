<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $table = 'checklist_templates';

    protected $fillable = ['nome', 'descrizione', 'attivo', 'ordinamento'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    public function voci(): HasMany
    {
        return $this->hasMany(ChecklistVoce::class)->orderBy('ordinamento');
    }

    public function compilate(): HasMany
    {
        return $this->hasMany(ChecklistCompilata::class);
    }

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true)->orderBy('ordinamento');
    }
}
