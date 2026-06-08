<?php

namespace App\Models;

use App\Enums\AzioneDeposito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositoPneumatico extends Model
{
    protected $table = 'depositi_pneumatici';

    protected $fillable = [
        'pneumatico_id', 'azione', 'commessa_id', 'data_azione',
        'ubicazione', 'usura_percentuale', 'usura_note',
        'km_al_momento', 'user_id', 'note',
    ];

    protected $casts = [
        'azione'      => AzioneDeposito::class,
        'data_azione' => 'date',
    ];

    public function pneumatico(): BelongsTo
    {
        return $this->belongsTo(Pneumatico::class);
    }

    public function commessa(): BelongsTo
    {
        return $this->belongsTo(Commessa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
