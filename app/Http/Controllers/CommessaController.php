<?php

namespace App\Http\Controllers;

use App\Models\Allegato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommessaController extends Controller
{
    public function downloadAllegato(Allegato $allegato)
    {
        $this->authorize('view', $allegato->commessa);

        if (! Storage::exists($allegato->percorso)) {
            abort(404, 'File non trovato.');
        }

        return Storage::download($allegato->percorso, $allegato->nome_file);
    }
}
