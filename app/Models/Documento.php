<?php

namespace App\Models;

use App\Enums\MetodoPagamento;
use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Enums\TipoEmissione;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Documento extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'documenti';

    protected $fillable = [
        'tipo',
        'numero',
        'anno',
        'progressivo',
        'commessa_id',
        'cliente_id',
        'data_emissione',
        'data_scadenza',
        'imponibile',
        'iva_totale',
        'totale',
        'stato',
        'metodo_pagamento',
        'note',
        'xml_generato',
        'xml_hash',
        'nome_file_sdi',
        'sinistro_id',
        'tipo_emissione',
        'documento_correlato_id',
        'casa_madre_id',
        'tipo_emissione_garanzia',
    ];

    protected function casts(): array
    {
        return [
            'tipo'             => TipoDocumento::class,
            'stato'            => StatoDocumento::class,
            'metodo_pagamento' => MetodoPagamento::class,
            'tipo_emissione'   => TipoEmissione::class,
            'data_emissione'   => 'date',
            'data_scadenza'    => 'date',
            'imponibile'       => 'decimal:2',
            'iva_totale'       => 'decimal:2',
            'totale'           => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['stato', 'numero'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->causer_id = auth()->id();
    }

    public function commessa()
    {
        return $this->belongsTo(Commessa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function righe()
    {
        return $this->hasMany(DocumentoRiga::class)->orderBy('ordinamento');
    }

    public function pagamenti()
    {
        return $this->hasMany(Pagamento::class)->orderBy('data_pagamento');
    }

    public function registroIva()
    {
        return $this->hasMany(RegistroIva::class);
    }

    public function sdiLog()
    {
        return $this->hasMany(SdiLog::class)->latest('created_at');
    }

    public function sinistro()
    {
        return $this->belongsTo(Sinistro::class);
    }

    public function documentoCorrelato()
    {
        return $this->belongsTo(Documento::class, 'documento_correlato_id');
    }

    public function documentiCorrelati()
    {
        return $this->hasMany(Documento::class, 'documento_correlato_id');
    }

    public function casaMadre()
    {
        return $this->belongsTo(CasaMadre::class);
    }

    public function getTotalePagatoAttribute(): float
    {
        return (float) $this->pagamenti->sum('importo');
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->totale - $this->totale_pagato, 2);
    }

    public function isImmutabile(): bool
    {
        return $this->stato->isImmutabile();
    }
}
