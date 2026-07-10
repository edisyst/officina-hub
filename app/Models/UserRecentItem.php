<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserRecentItem extends Model
{
    protected $table = 'user_recent_items';

    protected $fillable = [
        'user_id',
        'recordable_type',
        'recordable_id',
        'last_visited_at',
        'visits',
    ];

    protected $casts = [
        'last_visited_at' => 'datetime',
        'visits' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }
}
