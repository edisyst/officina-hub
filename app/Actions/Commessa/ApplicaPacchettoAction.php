<?php

namespace App\Actions\Commessa;

use App\Models\Commessa;
use App\Models\PacchettoServizio;
use Illuminate\Support\Facades\DB;

class ApplicaPacchettoAction
{
    /**
     * Crea le righe commessa dal pacchetto (con eventuali personalizzazioni)
     * e incrementa il contatore utilizzi del pacchetto.
     *
     * @param  array<int, array{tipo:string, descrizione:string, articolo_id:int|null, quantita:float, prezzo_unitario:float, sconto_percentuale:float, iva_percentuale:float}>  $righePersonalizzate
     * @return \App\Models\CommessaRiga[]
     */
    public function execute(Commessa $commessa, PacchettoServizio $pacchetto, array $righePersonalizzate): array
    {
        return DB::transaction(function () use ($commessa, $pacchetto, $righePersonalizzate) {
            $righeCreate = [];
            $ordinamentoBase = ($commessa->righe()->max('ordinamento') ?? 0) + 1;

            foreach ($righePersonalizzate as $index => $rigaDati) {
                $dati = [
                    'tipo'                => $rigaDati['tipo'],
                    'articolo_id'         => ($rigaDati['tipo'] === 'articolo') ? ($rigaDati['articolo_id'] ?? null) : null,
                    'tariffa_manodopera_id' => ($rigaDati['tipo'] === 'manodopera') ? ($rigaDati['tariffa_manodopera_id'] ?? null) : null,
                    'pacchetto_servizio_id' => $pacchetto->id,
                    'descrizione'         => $rigaDati['descrizione'],
                    'quantita'            => $rigaDati['tipo'] === 'nota' ? 1 : ($rigaDati['quantita'] ?? 1),
                    'prezzo_unitario'     => $rigaDati['tipo'] === 'nota' ? 0 : ($rigaDati['prezzo_unitario'] ?? 0),
                    'sconto_percentuale'  => $rigaDati['tipo'] === 'nota' ? 0 : ($rigaDati['sconto_percentuale'] ?? 0),
                    'iva_percentuale'     => $rigaDati['tipo'] === 'nota' ? 22 : ($rigaDati['iva_percentuale'] ?? 22),
                    'ordinamento'         => $ordinamentoBase + $index,
                ];

                $righeCreate[] = $commessa->righe()->create($dati);
            }

            $pacchetto->increment('utilizzi');

            return $righeCreate;
        });
    }
}
