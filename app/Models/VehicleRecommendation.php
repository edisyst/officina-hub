<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleRecommendation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_recommendations';

    protected $fillable = [
        'vehicle_id',
        'origin_work_order_id',
        'resolved_work_order_id',
        'source',
        'title',
        'description',
        'due_date',
        'due_km',
        'status',
        'dismissed_reason',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function veicolo()
    {
        return $this->belongsTo(Veicolo::class, 'vehicle_id');
    }

    public function originWorkOrder()
    {
        return $this->belongsTo(Commessa::class, 'origin_work_order_id');
    }

    public function resolvedWorkOrder()
    {
        return $this->belongsTo(Commessa::class, 'resolved_work_order_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
