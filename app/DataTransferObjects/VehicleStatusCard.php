<?php

namespace App\DataTransferObjects;

use App\Enums\StatoCommessa;
use Carbon\Carbon;

class VehicleStatusCard
{
    public function __construct(
        // Veicolo
        public readonly int    $veicoloId,
        public readonly string $targa,
        public readonly string $marca,
        public readonly string $modello,

        // Cliente
        public readonly string  $clienteNome,
        public readonly ?string $clienteTelefono,

        // OdL attivo (null se solo storico)
        public readonly ?int          $commessaId,
        public readonly ?string       $commessaNumero,
        public readonly ?StatoCommessa $commessaStato,
        public readonly ?Carbon       $dataIngresso,
        public readonly ?Carbon       $consegnaPrevista,  // data_ora_consegna_prevista

        // Semaforo ricambi: 'verde' | 'giallo' | 'grigio'
        public readonly string $semaforoRicambi,
        /** @var array<int, array{descrizione: string, statoOrdine: ?string, dataOrdinePrevista: ?string}> */
        public readonly array  $ricambiMancanti,

        // Step 10: approvazione DVI (null se Step 10 assente o nessun DVI)
        public readonly ?string $dviStato,
        public readonly ?Carbon $dviData,

        // Step 30: ultima comunicazione (null se Step 30 assente o nessuna comm.)
        public readonly ?string $comunicazioneCanale,
        public readonly ?Carbon $comunicazioneData,
        public readonly ?string $comunicazioneEstratto,

        // Fallback: ultimo OdL storico (usato solo se nessun OdL attivo)
        public readonly ?string $ultimaConsegnaLabel,  // es. "Consegnata il 15/06/2026"
    ) {}
}
