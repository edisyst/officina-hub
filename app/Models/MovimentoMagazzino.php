<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class MovimentoMagazzino extends Model
{
    use LogsActivity;

    protected $table = 'movimenti_magazzino';

    // i movimenti sono immutabili
    public $timestamps = false;

    protected $fillable = [
        'articolo_id',
        'tipo',
        'quantita',
        'giacenza_precedente',
        'giacenza_successiva',
        'prezzo_unitario',
        'commessa_id',
        'documento_fornitore',
        'data_documento',
        'user_id',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tipo'           => TipoMovimento::class,
            'prezzo_unitario' => 'decimal:2',
            'data_documento' => 'date',
            'created_at'     => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->causer_id = auth()->id();
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class);
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
