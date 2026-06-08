<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatoPrestito;
use App\Http\Controllers\Controller;
use App\Models\PrestitoCortesia;
use App\Models\VeicoloCortesia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CortesiaDisponibilitaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        // Risorse: ogni veicolo di cortesia attivo
        $veicoli = VeicoloCortesia::attivi()->orderBy('targa')->get();
        $risorse = $veicoli->map(fn($v) => [
            'id'    => 'cortesia_' . $v->id,
            'title' => "{$v->targa} — {$v->marca} {$v->modello}",
        ]);

        // Eventi: prestiti nel range
        $query = PrestitoCortesia::with(['cliente', 'veicolo'])
            ->whereIn('stato', [StatoPrestito::Prenotato, StatoPrestito::InCorso, StatoPrestito::Rientrato]);

        if ($start) {
            $query->where('data_rientro_prevista', '>=', substr($start, 0, 10));
        }
        if ($end) {
            $query->whereDate('data_consegna', '<=', $end);
        }

        $eventi = $query->get()->map(function (PrestitoCortesia $p) {
            return [
                'id'         => $p->id,
                'resourceId' => 'cortesia_' . $p->veicolo_cortesia_id,
                'title'      => $p->cliente->nome_completo . ' — ' . $p->veicolo->targa,
                'start'      => $p->data_consegna->toIso8601String(),
                'end'        => $p->data_rientro_effettiva
                    ? $p->data_rientro_effettiva->toIso8601String()
                    : $p->data_rientro_prevista->toDateString(),
                'color'      => $p->stato->colorCalendario(),
                'extendedProps' => [
                    'stato'       => $p->stato->value,
                    'prestito_id' => $p->id,
                    'in_ritardo'  => $p->isInRitardo(),
                ],
            ];
        });

        return response()->json([
            'risorse' => $risorse,
            'eventi'  => $eventi,
        ]);
    }
}
