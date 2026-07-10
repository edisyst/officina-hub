<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSavedFilter extends Model
{
    protected $table = 'user_saved_filters';

    protected $fillable = [
        'user_id',
        'page_key',
        'name',
        'filters',
        'is_default',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
