<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    use HasFactory;

    protected $table = 'communications';

    protected $fillable = [
        'customer_id',
        'work_order_id',
        'user_id',
        'channel',
        'direction',
        'subject',
        'body',
        'occurred_at',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta'        => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'customer_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(Commessa::class, 'work_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForWorkOrderContext($query, Commessa $commessa)
    {
        return $query->where(function ($q) use ($commessa) {
            $q->where('work_order_id', $commessa->id)
              ->orWhere(function ($q2) use ($commessa) {
                  $q2->where('customer_id', $commessa->cliente_id)
                     ->whereNull('work_order_id');
              });
        });
    }

    public function channelIcon(): string
    {
        return match($this->channel) {
            'whatsapp' => 'fab fa-whatsapp bg-success',
            'sms'      => 'fas fa-sms bg-info',
            'email'    => 'fas fa-envelope bg-primary',
            'phone'    => 'fas fa-phone bg-warning',
            'note'     => 'fas fa-sticky-note bg-secondary',
        };
    }

    public function channelLabel(): string
    {
        return match($this->channel) {
            'whatsapp' => 'WhatsApp',
            'sms'      => 'SMS',
            'email'    => 'Email',
            'phone'    => 'Telefono',
            'note'     => 'Nota',
        };
    }
}
