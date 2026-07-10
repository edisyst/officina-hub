<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShortcut extends Model
{
    protected $table = 'user_shortcuts';

    protected $fillable = [
        'user_id',
        'label',
        'url',
        'icon',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
