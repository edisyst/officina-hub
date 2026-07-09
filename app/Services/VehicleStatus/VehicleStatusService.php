<?php

namespace App\Services\VehicleStatus;

use App\DataTransferObjects\VehicleStatusCard;
use App\Enums\StatoCommessa;
use App\Enums\TipoMovimento;
use App\Enums\TipoRiga;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\DviIspezione;
use App\Models\OrdineFornitore;
use App\Models\Veicolo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class VehicleStatusService
{
    private bool $hasStep10;
    private bool $hasStep15;
    private bool $hasStep30;

    public function __construct()
    {
        $this->hasStep10 = Schema::hasTable('dvi_ispezioni');
        $this->hasStep15 = Schema::hasTable('ordine_fornitore_righe');
        $this->hasStep30 = Schema::hasTable('communications');
    }

    /** Cerca per targa (prefix), cognome/nome/ragione_sociale, telefono normalizzato. Max 5 risultati. */
    public function lookup(string $term): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $veicoloIds = $this->resolveVeicoloIds($term);
        if ($veicoloIds->isEmpty()) {
            return collect();
        }

        $veicoli = Veicolo::with('clientePrincipale')
            ->whereIn('id', $veicoloIds)
            ->get();

        $statiChiusi = [StatoCommessa::Consegnata, StatoCommessa::Fatturata];

        // Carica tutte le commesse con righe articolo e scarichi
        $commessePerVeicolo = Commessa::with([
            'righe' => fn ($q) => $q
                ->where('tipo', TipoRiga::Articolo->value)
                ->whereNotNull('articolo_id')
                ->with('articolo'),
            'movimentiMagazzino' => fn ($q) => $q->where('tipo', TipoMovimento::Scarico->value),
        ])
            ->whereIn('veicolo_id', $veicoloIds)
            ->orderByDesc('data_ingresso')
            ->get()
            ->groupBy('veicolo_id');

        // Trova commessa attiva per ogni veicolo
        $activeCommesseMap = $veicoli->mapWithKeys(function ($v) use ($commessePerVeicolo, $statiChiusi) {
            $commesse = $commessePerVeicolo->get($v->id, collect());
            $attiva = $commesse->first(fn ($c) => ! in_array($c->stato, $statiChiusi));
            return [$v->id => $attiva];
        });

        $activeIds = $activeCommesseMap->filter()->pluck('id');

        // Batch: DVI ispezioni (Step 10)
        $dviPerCommessa = collect();
        if ($this->hasStep10 && $activeIds->isNotEmpty()) {
            $dviPerCommessa = DviIspezione::whereIn('commessa_id', $activeIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('commessa_id')
                ->map(fn ($g) => $g->first());
        }

        // Batch: comunicazioni (Step 30)
        $commPerCommessa = collect();
        if ($this->hasStep30 && $activeIds->isNotEmpty()) {
            $commPerCommessa = Communication::whereIn('work_order_id', $activeIds)
                ->orderByDesc('occurred_at')
                ->get()
                ->groupBy('work_order_id')
                ->map(fn ($g) => $g->first());
        }

        // Batch: ordini fornitore pendenti per articoli (Step 15)
        $ordiniPerArticolo = collect();
        if ($this->hasStep15 && $activeIds->isNotEmpty()) {
            $articoloIds = $commessePerVeicolo
                ->flatten()
                ->filter(fn ($c) => $activeIds->contains($c->id))
                ->flatMap(fn ($c) => $c->righe->pluck('articolo_id'))
                ->filter()
                ->unique();

            if ($articoloIds->isNotEmpty()) {
                $statiPendenti = [
                    \App\Enums\StatoOrdineFornitore::Bozza->value,
                    \App\Enums\StatoOrdineFornitore::Inviato->value,
                    \App\Enums\StatoOrdineFornitore::Confermato->value,
                    \App\Enums\StatoOrdineFornitore::ParzialmenteRicevuto->value,
                ];
                $ordiniPerArticolo = \App\Models\OrdineFornitoreRiga::with('ordine')
                    ->whereIn('articolo_id', $articoloIds)
                    ->whereHas('ordine', fn ($q) => $q->whereIn('stato', $statiPendenti))
                    ->get()
                    ->groupBy('articolo_id')
                    ->map(fn ($g) => $g->first());
            }
        }

        return $veicoli->map(fn ($v) => $this->buildCard(
            $v,
            $commessePerVeicolo->get($v->id, collect()),
            $statiChiusi,
            $activeCommesseMap->get($v->id),
            $dviPerCommessa,
            $commPerCommessa,
            $ordiniPerArticolo,
        ));
    }

    private function resolveVeicoloIds(string $term): Collection
    {
        $byTarga = Veicolo::where('targa', 'like', $term . '%')
            ->limit(5)
            ->pluck('id');

        $byCliente = \App\Models\Cliente::where(function ($q) use ($term) {
            $q->where('nome', 'like', "%{$term}%")
              ->orWhere('cognome', 'like', "%{$term}%")
              ->orWhere('ragione_sociale', 'like', "%{$term}%")
              ->orWhere('telefono', 'like', "%{$term}%");
        })
        ->limit(10)
        ->pluck('id');

        $byClienteVeicoli = collect();
        if ($byCliente->isNotEmpty()) {
            $byClienteVeicoli = \Illuminate\Support\Facades\DB::table('cliente_veicolo')
                ->whereIn('cliente_id', $byCliente)
                ->pluck('veicolo_id');
        }

        return $byTarga->merge($byClienteVeicoli)->unique()->take(5)->values();
    }

    private function buildCard(
        \App\Models\Veicolo $veicolo,
        Collection $tutteCommesse,
        array $statiChiusi,
        ?Commessa $commessaAttiva,
        Collection $dviPerCommessa,
        Collection $commPerCommessa,
        Collection $ordiniPerArticolo,
    ): VehicleStatusCard {
        $cliente = $veicolo->clientePrincipale;
        $clienteNome = $cliente
            ? ($cliente->ragione_sociale ?? trim("{$cliente->nome} {$cliente->cognome}"))
            : '—';

        // Semaforo e ricambi
        [$semaforo, $mancanti] = $this->calcolaSemaforo($commessaAttiva, $ordiniPerArticolo);

        // DVI (Step 10)
        $dviStato = null;
        $dviData = null;
        if ($this->hasStep10 && $commessaAttiva) {
            $dvi = $dviPerCommessa->get($commessaAttiva->id);
            if ($dvi) {
                $dviStato = $dvi->stato->label();
                $dviData  = $dvi->approvata_at ?? $dvi->inviata_at ?? $dvi->created_at;
            }
        }

        // Comunicazione (Step 30)
        $commCanale   = null;
        $commData     = null;
        $commEstratto = null;
        if ($this->hasStep30 && $commessaAttiva) {
            $comm = $commPerCommessa->get($commessaAttiva->id);
            if ($comm) {
                $commCanale   = $comm->channelLabel();
                $commData     = $comm->occurred_at;
                $commEstratto = mb_substr($comm->body ?? $comm->subject ?? '', 0, 80);
            }
        }

        // Fallback storico
        $ultimaConsegnaLabel = null;
        if (! $commessaAttiva) {
            $storica = $tutteCommesse->first(fn ($c) => in_array($c->stato, $statiChiusi));
            if ($storica?->data_consegna) {
                $ultimaConsegnaLabel = 'Consegnata il ' . $storica->data_consegna->format('d/m/Y');
            }
        }

        return new VehicleStatusCard(
            veicoloId:             $veicolo->id,
            targa:                 $veicolo->targa,
            marca:                 $veicolo->marca ?? '',
            modello:               $veicolo->modello ?? '',
            clienteNome:           $clienteNome,
            clienteTelefono:       $cliente?->telefono,
            commessaId:            $commessaAttiva?->id,
            commessaNumero:        $commessaAttiva?->numero,
            commessaStato:         $commessaAttiva?->stato,
            dataIngresso:          $commessaAttiva?->data_ingresso,
            consegnaPrevista:      $commessaAttiva?->data_ora_consegna_prevista,
            semaforoRicambi:       $semaforo,
            ricambiMancanti:       $mancanti,
            dviStato:              $dviStato,
            dviData:               $dviData,
            comunicazioneCanale:   $commCanale,
            comunicazioneData:     $commData,
            comunicazioneEstratto: $commEstratto,
            ultimaConsegnaLabel:   $ultimaConsegnaLabel,
        );
    }

    /** Restituisce ['verde'|'giallo'|'grigio', $righe_mancanti] */
    private function calcolaSemaforo(?Commessa $commessa, Collection $ordiniPerArticolo): array
    {
        if (! $commessa) {
            return ['grigio', []];
        }

        $righeArticolo = $commessa->righe; // già filtrate su tipo=Articolo+articolo_id

        if ($righeArticolo->isEmpty()) {
            return ['grigio', []];
        }

        $mancanti = [];
        $tutteVerde = true;

        foreach ($righeArticolo as $riga) {
            $articolo = $riga->articolo;
            if (! $articolo) {
                continue;
            }

            // Riga già scaricata per questa commessa?
            $giaScaricata = $commessa->movimentiMagazzino
                ->where('articolo_id', $articolo->id)
                ->isNotEmpty();

            if ($giaScaricata) {
                continue; // verde per questa riga
            }

            // Giacenza sufficiente?
            if ($articolo->giacenza_attuale >= (float) $riga->quantita) {
                continue; // disponibile in magazzino
            }

            // Giallo: manca
            $tutteVerde = false;

            $mancante = ['descrizione' => $riga->descrizione ?: $articolo->descrizione];

            if ($this->hasStep15) {
                $ordineRiga = $ordiniPerArticolo->get($articolo->id);
                if ($ordineRiga) {
                    $mancante['statoOrdine']        = $ordineRiga->ordine->stato->label();
                    $mancante['dataOrdinePrevista']  = $ordineRiga->ordine->data_consegna_prevista?->format('d/m/Y');
                }
            }

            $mancanti[] = $mancante;
        }

        return $tutteVerde ? ['verde', []] : ['giallo', $mancanti];
    }
}
