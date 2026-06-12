<?php

namespace App\Http\Controllers;

use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Veicolo;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $commesseAperte = Commessa::whereIn('stato', [
            StatoCommessa::Bozza->value,
            StatoCommessa::Accettata->value,
            StatoCommessa::InLavorazione->value,
        ])->count();

        $commessePronte = Commessa::where('stato', StatoCommessa::Completata->value)->count();

        $totaleClienti = Cliente::count();

        $totaleVeicoli = Veicolo::count();

        $ultimeCommesse = Commessa::with(['cliente', 'veicolo'])
            ->latest('data_ingresso')
            ->limit(10)
            ->get();

        $conteggioPerStato = collect(StatoCommessa::cases())
            ->mapWithKeys(fn($stato) => [
                $stato->value => Commessa::where('stato', $stato->value)->count(),
            ]);

        return view('dashboard', compact(
            'commesseAperte',
            'commessePronte',
            'totaleClienti',
            'totaleVeicoli',
            'ultimeCommesse',
            'conteggioPerStato',
        ));
    }
}
