<?php

namespace App\Http\Controllers;

use App\Models\Allegato;
use App\Models\Commessa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommessaController extends Controller
{
    public function stampaMassiva(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->query('ids', ''))));

        if (empty($ids) || count($ids) > 200) {
            abort(400, 'Parametro ids mancante o troppo grande (max 200).');
        }

        $commesse = Commessa::with(['cliente', 'veicolo', 'righe'])
            ->whereIn('id', $ids)
            ->latest('data_ingresso')
            ->get();

        return view('print.work-orders-batch', compact('commesse'));
    }

    public function downloadAllegato(Allegato $allegato)
    {
        $this->authorize('view', $allegato->commessa);

        if (! Storage::exists($allegato->percorso)) {
            abort(404, 'File non trovato.');
        }

        return Storage::download($allegato->percorso, $allegato->nome_file);
    }
}
