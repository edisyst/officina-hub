<?php

namespace App\Services\Commesse;

use App\DataTransferObjects\CommessaMargins;
use App\Enums\TipoRiga;
use App\Models\Commessa;

class MarginCalculatorService
{
    public function calcola(Commessa $commessa): CommessaMargins
    {
        $commessa->loadMissing(['righe.articolo', 'lavorazioni.meccanico']);

        $laborCostConfig = config('margins.labor_cost_per_hour');

        $ricavoRicambi = 0.0;
        $costoRicambi  = 0.0;
        $ricavoManodopera = 0.0;
        $orePreventivate  = 0.0;
        $righeRicambiSenzaCosto    = 0;
        $righeManodoperaSenzaStima = 0;

        foreach ($commessa->righe as $riga) {
            $imponibile = (float) $riga->quantita * (float) $riga->prezzo_unitario
                * (1 - (float) $riga->sconto_percentuale / 100);

            if ($riga->tipo === TipoRiga::Manodopera) {
                $ricavoManodopera += $imponibile;
                if ($riga->ore_preventivate !== null) {
                    $orePreventivate += (float) $riga->ore_preventivate;
                } else {
                    $righeManodoperaSenzaStima++;
                }
            } elseif ($riga->tipo === TipoRiga::Articolo) {
                $ricavoRicambi += $imponibile;
                if ($riga->prezzo_acquisto !== null && (float) $riga->prezzo_acquisto > 0) {
                    $costoRicambi += (float) $riga->prezzo_acquisto * (float) $riga->quantita;
                } else {
                    $righeRicambiSenzaCosto++;
                }
            }
        }

        $oreEffettive    = 0.0;
        $costoManodopera = null;

        foreach ($commessa->lavorazioni as $lav) {
            if ($lav->minuti_effettivi !== null) {
                $ore = $lav->minuti_effettivi / 60;
                $oreEffettive += $ore;

                if ($laborCostConfig !== null) {
                    $costo = $lav->meccanico?->costo_orario
                        ? (float) $lav->meccanico->costo_orario
                        : (float) $laborCostConfig;
                    $costoManodopera = ($costoManodopera ?? 0.0) + $ore * $costo;
                }
            }
        }

        $margineRicambi     = $ricavoRicambi - $costoRicambi;
        $margineRicambiPerc = $ricavoRicambi > 0 ? round($margineRicambi / $ricavoRicambi * 100, 1) : 0.0;

        $ricavoTotale = $ricavoRicambi + $ricavoManodopera;
        $costoTotale  = $costoRicambi + ($costoManodopera ?? 0.0);
        $margineTotale = $ricavoTotale - $costoTotale;
        $margineTotalePerc = $ricavoTotale > 0 ? round($margineTotale / $ricavoTotale * 100, 1) : 0.0;

        $deltaOre = $oreEffettive - $orePreventivate;

        return new CommessaMargins(
            ricavoRicambi:             round($ricavoRicambi, 2),
            costoRicambi:              round($costoRicambi, 2),
            margineRicambi:            round($margineRicambi, 2),
            margineRicambiPerc:        $margineRicambiPerc,
            ricavoManodopera:          round($ricavoManodopera, 2),
            costoManodopera:           $costoManodopera !== null ? round($costoManodopera, 2) : null,
            orePreventivate:           round($orePreventivate, 2),
            oreEffettive:              round($oreEffettive, 2),
            deltaOre:                  round($deltaOre, 2),
            ricavoTotale:              round($ricavoTotale, 2),
            costoTotale:               round($costoTotale, 2),
            margineTotale:             round($margineTotale, 2),
            margineTotalePerc:         $margineTotalePerc,
            righeRicambiSenzaCosto:    $righeRicambiSenzaCosto,
            righeManodoperaSenzaStima: $righeManodoperaSenzaStima,
        );
    }
}
