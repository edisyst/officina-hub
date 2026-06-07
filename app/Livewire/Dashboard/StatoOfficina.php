<?php

namespace App\Livewire\Dashboard;

use App\Enums\StatoCommessa;
use App\Enums\StatoDviIspezione;
use App\Models\Appuntamento;
use App\Models\Articolo;
use App\Models\Commessa;
use App\Models\DviIspezione;
use App\Models\Lavorazione;
use App\Models\Ponte;
use Livewire\Attributes\Poll;
use Livewire\Component;

#[Poll(30000)]
class StatoOfficina extends Component
{
    public function render()
    {
        $user = auth()->user();
        $dati = [];

        if ($user->hasAnyRole(['admin', 'accettatore'])) {
            $dati['commesse_in_lavorazione'] = Commessa::where('stato', StatoCommessa::InLavorazione)->count();

            $dati['appuntamenti_oggi'] = Appuntamento::with(['cliente', 'veicolo'])
                ->whereDate('data_ora_inizio', today())
                ->orderBy('data_ora_inizio')
                ->get();

            $dati['prossimi_appuntamenti'] = Appuntamento::with(['cliente', 'veicolo'])
                ->where('data_ora_inizio', '>', now())
                ->where('data_ora_inizio', '<=', now()->addHours(24))
                ->orderBy('data_ora_inizio')
                ->get();

            $dati['commesse_attesa_consegna'] = Commessa::where('stato', StatoCommessa::Completata)->count();

            $pontiOccupati = Lavorazione::attive()
                ->whereNotNull('ponte_id')
                ->pluck('ponte_id')
                ->toArray();

            $dati['ponti'] = Ponte::attivi()->get()->map(function ($ponte) use ($pontiOccupati) {
                $ponte->occupato = in_array($ponte->id, $pontiOccupati);
                return $ponte;
            });

            // Widget sotto scorta (aggiornato col polling da 30s)
            $dati['articoli_sotto_scorta'] = Articolo::with('fornitore')
                ->attivi()
                ->sottoScorta()
                ->orderBy('descrizione')
                ->get();

            $dati['count_sotto_scorta'] = $dati['articoli_sotto_scorta']->count();

            // Widget DVI
            $dati['dvi_in_attesa'] = DviIspezione::with(['commessa.cliente', 'commessa.veicolo'])
                ->where('stato', StatoDviIspezione::InviataCliente)
                ->orderBy('inviata_at')
                ->get();

            $dati['dvi_risposta_oggi'] = DviIspezione::whereIn('stato', [
                    StatoDviIspezione::Approvata,
                    StatoDviIspezione::ParzialmenteApprovata,
                    StatoDviIspezione::Rifiutata,
                ])
                ->whereDate('approvata_at', today())
                ->count();
        }

        if ($user->hasRole('meccanico')) {
            $dati['mie_commesse'] = Commessa::with(['cliente', 'veicolo'])
                ->where('stato', StatoCommessa::InLavorazione)
                ->where('user_id', $user->id)
                ->get();
        }

        if ($user->hasRole('cassa')) {
            $dati['commesse_da_fatturare'] = Commessa::with(['cliente', 'veicolo'])
                ->where('stato', StatoCommessa::Completata)
                ->orderBy('updated_at')
                ->get();
        }

        return view('livewire.dashboard.stato-officina', compact('dati'));
    }
}
