<?php

namespace App\Models;

use App\Enums\StatoCarrozzeria;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\Communication;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Commessa extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'commesse';

    protected $fillable = [
        'numero',
        'cliente_id',
        'veicolo_id',
        'tipo',
        'stato',
        'km_ingresso',
        'data_ingresso',
        'data_uscita_prevista',
        'data_consegna',
        'descrizione_cliente',
        'diagnosi_tecnica',
        'note_interne',
        'firma_cliente_svg',
        'firma_consegna_svg',
        'user_id',
        'sinistro_id',
        'stato_carrozzeria',
        'km_uscita',
        'note_accettazione_json',
        'dvi_approvazione_importo',
        'ha_righe_garanzia',
        'board_position',
        'data_ora_consegna_prevista',
    ];

    protected function casts(): array
    {
        return [
            'tipo'                  => TipoCommessa::class,
            'stato'                 => StatoCommessa::class,
            'stato_carrozzeria'     => StatoCarrozzeria::class,
            'data_ingresso'          => 'datetime',
            'data_uscita_prevista'   => 'date',
            'data_consegna'                => 'datetime',
            'data_ora_consegna_prevista'   => 'datetime',
            'note_accettazione_json' => 'array',
            'ha_righe_garanzia'      => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['stato', 'data_consegna', 'km_ingresso'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->causer_id = auth()->id();
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veicolo()
    {
        return $this->belongsTo(Veicolo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function righe()
    {
        return $this->hasMany(CommessaRiga::class)->orderBy('ordinamento');
    }

    public function allegati()
    {
        return $this->hasMany(Allegato::class);
    }

    public function log()
    {
        return $this->hasMany(CommessaLog::class)->latest();
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'work_order_id')->latest('occurred_at');
    }

    public function appuntamenti()
    {
        return $this->hasMany(Appuntamento::class);
    }

    public function lavorazioni()
    {
        return $this->hasMany(Lavorazione::class)->orderBy('created_at');
    }

    public function movimentiMagazzino()
    {
        return $this->hasMany(MovimentoMagazzino::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function sinistro()
    {
        return $this->belongsTo(Sinistro::class);
    }

    public function danni()
    {
        return $this->hasMany(DannoVeicolo::class)->orderBy('ordinamento')->orderBy('id');
    }

    public function fotoDanni()
    {
        return $this->hasMany(FotoDanno::class);
    }

    /** Verifica se la transizione di stato richiesta è ammessa */
    public function puoTransireA(StatoCommessa $nuovoStato): bool
    {
        return $this->stato->puoTransire($nuovoStato);
    }

    /** Calcola il totale imponibile delle righe (escluse declined) */
    public function getTotaleImponibileAttribute(): float
    {
        return $this->righe->where('outcome', '!=', 'declined')->sum(function ($riga) {
            $importo = $riga->quantita * $riga->prezzo_unitario;
            return $importo * (1 - $riga->sconto_percentuale / 100);
        });
    }

    /** Calcola il totale IVA (escluse declined) */
    public function getTotaleIvaAttribute(): float
    {
        return $this->righe->where('outcome', '!=', 'declined')->sum(function ($riga) {
            $importo = $riga->quantita * $riga->prezzo_unitario;
            $imponibile = $importo * (1 - $riga->sconto_percentuale / 100);
            return $imponibile * ($riga->iva_percentuale / 100);
        });
    }

    public function getTotaleLordoAttribute(): float
    {
        return $this->totale_imponibile + $this->totale_iva;
    }

    /** Totale commessa a carico cliente (righe in garanzia o declined valgono 0) */
    public function getTotaleClienteAttribute(): float
    {
        return $this->righe->where('outcome', '!=', 'declined')->sum(fn($r) => $r->totale_cliente);
    }

    /** Totale commessa a carico case madri (solo righe in garanzia, escluse declined) */
    public function getTotaleCasaMadreAttribute(): float
    {
        return $this->righe->where('outcome', '!=', 'declined')->sum(fn($r) => $r->totale_casa_madre);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('numero', 'like', "%{$term}%")
              ->orWhereHas('cliente', fn($c) => $c->search($term))
              ->orWhereHas('veicolo', fn($v) => $v->search($term));
        });
    }
}
