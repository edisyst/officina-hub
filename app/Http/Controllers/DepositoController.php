<?php

namespace App\Http\Controllers;

use App\Models\Pneumatico;
use App\Services\EtichettaDepositoService;
use Illuminate\Http\Request;

class DepositoController extends Controller
{
    public function etichetta(int $id, EtichettaDepositoService $service)
    {
        $pneumatico = Pneumatico::findOrFail($id);
        return $service->genera($pneumatico);
    }

    public function etichettaMultiple(Request $request, EtichettaDepositoService $service)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        return $service->generaMultiple($ids);
    }

    public function cercaQr(string $codice)
    {
        // Codice formato DEP-2026-00042 → estrai id
        if (preg_match('/(\d+)$/', $codice, $m)) {
            $id = (int)$m[1];
            $p  = Pneumatico::find($id);
            if ($p) {
                return redirect()->route('veicoli.show', $p->veicolo_id)
                    . '#pneumatici';
            }
        }
        abort(404, 'Etichetta non trovata: ' . $codice);
    }
}
