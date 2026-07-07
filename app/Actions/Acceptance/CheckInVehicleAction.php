<?php

namespace App\Actions\Acceptance;

use App\Actions\Commessa\ApplicaPacchettoAction;
use App\Actions\Commessa\GeneraNumeroProgressivoAction;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PacchettoServizio;
use App\Models\Veicolo;
use Illuminate\Support\Facades\DB;

class CheckInVehicleAction
{
    /**
     * Crea o aggiorna cliente+veicolo, apre OdL in transazione unica.
     * Rollback completo su qualsiasi eccezione.
     *
     * @param  array{
     *   veicolo_id: int|null,
     *   modoNuovoVeicolo: bool,
     *   targa: string,
     *   km: int|null,
     *   nv_tipo: string,
     *   nv_marca: string,
     *   nv_modello: string,
     *   nv_anno: int|null,
     *   nv_vin: string|null,
     *   nv_alimentazione: string,
     *   cliente_id: int|null,
     *   modoNuovoCliente: bool,
     *   nc_tipo: string,
     *   nc_nome: string|null,
     *   nc_cognome: string|null,
     *   nc_ragione_sociale: string|null,
     *   nc_telefono: string|null,
     *   nc_email: string|null,
     *   nc_codice_fiscale: string|null,
     *   nc_partita_iva: string|null,
     *   tipo: string,
     *   descrizione_cliente: string,
     *   data_uscita_prevista: string|null,
     *   pacchetto_id: int|null,
     *   righe_preventivo: array,
     * } $dati
     */
    public function execute(array $dati, \App\Models\User $user): Commessa
    {
        return DB::transaction(function () use ($dati, $user) {
            $cliente = $this->resolveCliente($dati);
            $veicolo = $this->resolveVeicolo($dati, $cliente);

            $numero = app(GeneraNumeroProgressivoAction::class)->execute();

            $commessa = Commessa::create([
                'numero'               => $numero,
                'cliente_id'           => $cliente->id,
                'veicolo_id'           => $veicolo->id,
                'tipo'                 => $dati['tipo'],
                'stato'                => 'bozza',
                'km_ingresso'          => $dati['km'],
                'data_ingresso'        => now(),
                'data_uscita_prevista' => $dati['data_uscita_prevista'] ?: null,
                'descrizione_cliente'  => $dati['descrizione_cliente'],
                'user_id'              => $user->id,
            ]);

            if (! empty($dati['pacchetto_id']) && ! empty($dati['righe_preventivo'])) {
                $pacchetto = PacchettoServizio::find($dati['pacchetto_id']);
                if ($pacchetto) {
                    app(ApplicaPacchettoAction::class)->execute($commessa, $pacchetto, $dati['righe_preventivo']);
                }
            }

            return $commessa;
        });
    }

    private function resolveCliente(array $dati): Cliente
    {
        if (! $dati['modoNuovoCliente']) {
            return Cliente::findOrFail($dati['cliente_id']);
        }

        $payload = ['tipo' => $dati['nc_tipo']];

        if ($dati['nc_tipo'] === 'fisica') {
            $payload['nome']    = $dati['nc_nome'];
            $payload['cognome'] = $dati['nc_cognome'];
        } else {
            $payload['ragione_sociale'] = $dati['nc_ragione_sociale'];
        }

        foreach (['nc_telefono' => 'telefono', 'nc_email' => 'email', 'nc_codice_fiscale' => 'codice_fiscale', 'nc_partita_iva' => 'partita_iva'] as $src => $dst) {
            if (! empty($dati[$src])) {
                $payload[$dst] = $dati[$src];
            }
        }

        return Cliente::create($payload);
    }

    private function resolveVeicolo(array $dati, Cliente $cliente): Veicolo
    {
        if ($dati['modoNuovoVeicolo']) {
            $veicolo = Veicolo::create([
                'tipo'                 => $dati['nv_tipo'],
                'targa'                => $dati['targa'] ?: null,
                'marca'                => $dati['nv_marca'],
                'modello'              => $dati['nv_modello'],
                'anno_immatricolazione'=> $dati['nv_anno'],
                'vin'                  => $dati['nv_vin'] ?: null,
                'alimentazione'        => $dati['nv_alimentazione'],
                'km_attuali'           => $dati['km'],
                'cliente_id'           => $cliente->id,
            ]);

            $veicolo->clienti()->attach($cliente->id, [
                'proprietario_attuale' => true,
                'data_inizio'          => now()->toDateString(),
            ]);

            return $veicolo;
        }

        $veicolo = Veicolo::findOrFail($dati['veicolo_id']);

        if ($dati['km'] !== null && $dati['km'] > ($veicolo->km_attuali ?? 0)) {
            $veicolo->update(['km_attuali' => $dati['km']]);
        }

        return $veicolo;
    }
}
