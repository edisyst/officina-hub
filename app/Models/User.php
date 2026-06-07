<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'costo_orario',
        'colore',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'costo_orario',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'costo_orario'      => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->causer_id = auth()->id();
    }

    public function commesse()
    {
        return $this->hasMany(Commessa::class);
    }

    public function appuntamenti()
    {
        return $this->hasMany(Appuntamento::class);
    }

    public function lavorazioni()
    {
        return $this->hasMany(Lavorazione::class);
    }

    public function lavorazioniAttive()
    {
        return $this->hasMany(Lavorazione::class)->attive();
    }
}
