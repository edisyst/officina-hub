<?php

namespace App\Observers;

use App\Actions\Magazzino\ScaricoCommessaAction;
use App\Actions\Scadenze\CreaScadenzeAutomaticheAction;
use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Services\Analytics\KpiService;

class CommessaObserver
{
    public function updated(Commessa $commessa): void
    {
        // Invalida cache KPI quando cambia lo stato di una commessa
        if ($commessa->wasChanged('stato')) {
            KpiService::invalidaCache();
        }

        if (! $commessa->wasChanged('stato')) {
            return;
        }

        // Scarico automatico magazzino al passaggio a "completata"
        if ($commessa->stato === StatoCommessa::Completata) {
            $utente = auth()->user() ?? $commessa->user;
            if ($utente) {
                app(ScaricoCommessaAction::class)->execute($commessa, $utente);
            }
        }

        // Suggerisce scadenze automatiche al passaggio a "consegnata"
        if ($commessa->stato === StatoCommessa::Consegnata) {
            $suggerimenti = app(CreaScadenzeAutomaticheAction::class)->suggerisci($commessa);

            if (count($suggerimenti) > 0) {
                // Serializza per passare via evento Livewire (date come stringa)
                $dati = array_map(fn($s) => [
                    'tipo'          => $s['tipo']->value,
                    'descrizione'   => $s['descrizione'],
                    'data_scadenza' => $s['data_scadenza']->format('Y-m-d'),
                    'km_scadenza'   => $s['km_scadenza'],
                ], $suggerimenti);

                // Dispatch evento Livewire verso il componente DettaglioCommessa
                \Livewire\Livewire::dispatch('scadenze-suggerite', [
                    'commessa_id' => $commessa->id,
                    'suggerimenti' => $dati,
                ]);
            }
        }
    }
}
