<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Scadenza;
use Illuminate\Http\Request;

class PortaleClienteController extends Controller
{
    public function show(Request $request, string $token)
    {
        // Verifica la firma dell'URL
        abort_unless($request->hasValidSignature(), 403, 'Link non valido o scaduto.');

        $clienteId = decrypt($token);
        $cliente = Cliente::findOrFail($clienteId);

        $commesse = $cliente->commesse()
            ->with('veicolo')
            ->latest('data_ingresso')
            ->take(5)
            ->get();

        $scadenze = Scadenza::where('cliente_id', $cliente->id)
            ->where('data_scadenza', '>=', now()->startOfDay())
            ->where('notifica_disabilitata', false)
            ->with('veicolo')
            ->orderBy('data_scadenza')
            ->take(10)
            ->get();

        return view('portale-cliente.index', compact('cliente', 'commesse', 'scadenze'));
    }
}
