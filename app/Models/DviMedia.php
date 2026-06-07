<?php

namespace App\Models;

use App\Enums\TipoDviMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DviMedia extends Model
{
    protected $table = 'dvi_media';

    protected $fillable = [
        'dvi_voce_id', 'dvi_ispezione_id', 'tipo',
        'percorso', 'nome_file', 'mime_type',
        'dimensione_bytes', 'durata_secondi', 'thumbnail_path', 'user_id',
    ];

    protected function casts(): array
    {
        return ['tipo' => TipoDviMedia::class];
    }

    public function voce(): BelongsTo
    {
        return $this->belongsTo(DviVoce::class, 'dvi_voce_id');
    }

    public function ispezione(): BelongsTo
    {
        return $this->belongsTo(DviIspezione::class, 'dvi_ispezione_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
