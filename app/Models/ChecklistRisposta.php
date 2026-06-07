<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistRisposta extends Model
{
    protected $table = 'checklist_risposte';

    protected $fillable = [
        'checklist_compilata_id',
        'checklist_voce_id',
        'valore_booleano',
        'valore_numerico',
        'valore_testo',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'valore_booleano'  => 'boolean',
            'valore_numerico'  => 'decimal:2',
        ];
    }

    public function compilata(): BelongsTo
    {
        return $this->belongsTo(ChecklistCompilata::class, 'checklist_compilata_id');
    }

    public function voce(): BelongsTo
    {
        return $this->belongsTo(ChecklistVoce::class, 'checklist_voce_id');
    }
}
