<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistVoce extends Model
{
    protected $table = 'checklist_voci';

    protected $fillable = [
        'checklist_template_id',
        'etichetta',
        'tipo',
        'obbligatoria',
        'unita_misura',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return ['obbligatoria' => 'boolean'];
    }

    // Valid types
    const TIPI = ['si_no', 'numerico', 'testo_libero', 'foto_obbligatoria'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function risposte(): HasMany
    {
        return $this->hasMany(ChecklistRisposta::class);
    }
}
