<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CasaMadre extends Model
{
    use SoftDeletes;

    protected $table = 'case_madri';

    protected $fillable = [
        'ragione_sociale',
        'partita_iva',
        'codice_destinatario_sdi',
        'pec',
        'email',
        'telefono',
        'codice_convenzionamento',
        'note',
    ];

    public function garanzie()
    {
        return $this->hasMany(Garanzia::class);
    }

    public function commessaRighe()
    {
        return $this->hasMany(CommessaRiga::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }
}
