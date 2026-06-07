<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perizia extends Model
{
    protected $table = 'perizie';

    protected $fillable = [
        'sinistro_id',
        'perito_nome',
        'perito_email',
        'data_sopralluogo',
        'data_ricezione',
        'importo_liquidato',
        'importo_franchigia',
        'importo_scoperto_percentuale',
        'importo_netto_liquidato',
        'note_perito',
        'allegato_perizia_path',
        'accettata',
        'motivo_contestazione',
    ];

    protected function casts(): array
    {
        return [
            'data_sopralluogo'             => 'date',
            'data_ricezione'               => 'date',
            'importo_liquidato'            => 'decimal:2',
            'importo_franchigia'           => 'decimal:2',
            'importo_scoperto_percentuale' => 'decimal:2',
            'importo_netto_liquidato'      => 'decimal:2',
            'accettata'                    => 'boolean',
        ];
    }

    public function sinistro()
    {
        return $this->belongsTo(Sinistro::class);
    }

    /** Calcola e imposta importo_netto_liquidato prima del salvataggio */
    public function calcolaNetto(): void
    {
        if ($this->importo_liquidato === null) {
            $this->importo_netto_liquidato = null;
            return;
        }

        $netto = (float) $this->importo_liquidato - (float) $this->importo_franchigia;
        if ((float) $this->importo_scoperto_percentuale > 0) {
            $netto = $netto * (1 - (float) $this->importo_scoperto_percentuale / 100);
        }
        $this->importo_netto_liquidato = max(0, round($netto, 2));
    }
}
