<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appuntamento;
use Illuminate\Http\Request;

class AppuntamentiController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $query = Appuntamento::with(['cliente', 'veicolo', 'ponte', 'meccanico']);

        if ($start) {
            $query->where('data_ora_fine', '>=', $start);
        }
        if ($end) {
            $query->where('data_ora_inizio', '<=', $end);
        }

        return $query->get()->map(function (Appuntamento $app) {
            $resourceId = null;
            if ($app->ponte_id) {
                $resourceId = 'ponte_' . $app->ponte_id;
            } elseif ($app->user_id) {
                $resourceId = 'mec_' . $app->user_id;
            }

            $colore = $app->meccanico?->colore
                ?? $app->stato->colorCalendario();

            return [
                'id'    => $app->id,
                'title' => $app->titolo_calendario,
                'start' => $app->tutto_il_giorno
                    ? $app->data_ora_inizio->toDateString()
                    : $app->data_ora_inizio->toIso8601String(),
                'end'   => $app->tutto_il_giorno
                    ? $app->data_ora_fine->toDateString()
                    : $app->data_ora_fine->toIso8601String(),
                'allDay'     => $app->tutto_il_giorno,
                'resourceId' => $resourceId,
                'color'      => $colore,
                'extendedProps' => [
                    'stato'       => $app->stato->value,
                    'commessa_id' => $app->commessa_id,
                    'note'        => $app->note,
                ],
            ];
        });
    }
}
