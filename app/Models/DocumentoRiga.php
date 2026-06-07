<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoRiga extends Model
{
    protected $table = 'documento_righe';

    protected $fillable = [
        'documento_id',
        'commessa_riga_id',
        'descrizione',
        'unita_misura',
        'quantita',
        'prezzo_unitario',
        'sconto_percentuale',
        'iva_percentuale',
        'natura_iva',
        'imponibile_riga',
        'iva_riga',
        'ordinamento',
    ];

    protected function casts(): array
    {
        return [
            'quantita'           => 'decimal:2',
            'prezzo_unitario'    => 'decimal:2',
            'sconto_percentuale' => 'decimal:2',
            'iva_percentuale'    => 'decimal:2',
            'imponibile_riga'    => 'decimal:2',
            'iva_riga'           => 'decimal:2',
        ];
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function commessaRiga()
    {
        return $this->belongsTo(CommessaRiga::class);
    }

    /** Ricalcola imponibile e IVA dai valori di input e li scrive sull'istanza */
    public function ricalcola(): void
    {
        $imp = (float) $this->quantita * (float) $this->prezzo_unitario;
        $imp = $imp * (1 - (float) $this->sconto_percentuale / 100);

        $this->imponibile_riga = round($imp, 2);
        $this->iva_riga        = round($imp * ((float) $this->iva_percentuale / 100), 2);
    }
}
