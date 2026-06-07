<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistCompilata extends Model
{
    protected $table = 'checklist_compilate';

    protected $fillable = [
        'checklist_template_id',
        'commessa_id',
        'user_id',
        'completata_at',
    ];

    protected function casts(): array
    {
        return ['completata_at' => 'datetime'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function commessa(): BelongsTo
    {
        return $this->belongsTo(Commessa::class);
    }

    public function meccanico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function risposte(): HasMany
    {
        return $this->hasMany(ChecklistRisposta::class);
    }

    public function isCompletata(): bool
    {
        return $this->completata_at !== null;
    }

    public function percentualeCompletamento(): int
    {
        $totale = $this->template->voci()->count();
        if ($totale === 0) return 100;

        $risposte = $this->risposte()->count();
        return intval(($risposte / $totale) * 100);
    }
}
