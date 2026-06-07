<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ponte;
use App\Models\User;
use Illuminate\Http\Request;

class RisorseAgendaController extends Controller
{
    public function index(Request $request)
    {
        $tipo    = $request->query('tipo'); // 'ponte' | 'meccanico' | null
        $risorse = [];

        if ($tipo !== 'meccanico') {
            foreach (Ponte::attivi()->get() as $ponte) {
                $risorse[] = [
                    'id'         => 'ponte_' . $ponte->id,
                    'title'      => $ponte->nome,
                    'eventColor' => '#00a65a',
                    'tipo'       => 'ponte',
                ];
            }
        }

        if ($tipo !== 'ponte') {
            foreach (User::role('meccanico')->get() as $meccanico) {
                $risorse[] = [
                    'id'         => 'mec_' . $meccanico->id,
                    'title'      => $meccanico->name,
                    'eventColor' => $meccanico->colore ?? '#3c8dbc',
                    'tipo'       => 'meccanico',
                ];
            }
        }

        return response()->json($risorse);
    }
}
