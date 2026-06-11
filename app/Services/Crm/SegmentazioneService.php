<?php

namespace App\Services\Crm;

use App\Enums\SegmentoCrm;
use App\Enums\StatoCommessa;
use App\Models\Cliente;
use App\Models\Documento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SegmentazioneService
{
    /**
     * Ricalcola segmento_crm, valore_lifetime, numero_visite, ultima_visita_at
     * per tutti i clienti attivi. Usato dal job notturno.
     */
    public function ricalcolaTutti(): int
    {
        $clienti = Cliente::withTrashed(false)->get();

        $valore90th = $this->calcolaPercentile90($clienti);

        $aggiornati = 0;
        foreach ($clienti as $cliente) {
            $this->aggiornaCliente($cliente, $valore90th);
            $aggiornati++;
        }

        return $aggiornati;
    }

    /**
     * Aggiornamento incrementale per un singolo cliente (da observer).
     */
    public function aggiornaIncrementale(Cliente $cliente): void
    {
        $valore90th = $this->calcolaPercentile90();
        $this->aggiornaCliente($cliente, $valore90th);
    }

    /**
     * Calcola il segmento basandosi sui valori già presenti sul modello (senza query).
     * Usato per test e ricalcoli veloci.
     */
    public function calcolaSegmentoDaModello(Cliente $cliente): SegmentoCrm
    {
        $valore90th = $this->calcolaPercentile90();

        $ultimaVisita = $cliente->ultima_visita_at;
        $numeroVisite = $cliente->numero_visite ?? 0;
        $valoreLifetime = (float) ($cliente->valore_lifetime ?? 0);

        $oggi = now();

        // VIP: top 10% per valore (solo se c'è un valore 90° percentile significativo)
        if ($valore90th > 0 && $valoreLifetime >= $valore90th) {
            return SegmentoCrm::Vip;
        }

        // Perso
        if ($ultimaVisita && $ultimaVisita->lt($oggi->copy()->subMonths(24))) {
            return SegmentoCrm::Perso;
        }

        // A rischio
        if ($ultimaVisita && $ultimaVisita->lt($oggi->copy()->subMonths(12))) {
            return SegmentoCrm::ARischio;
        }

        // Attivo
        if ($ultimaVisita && $ultimaVisita->gte($oggi->copy()->subMonths(12)) && $numeroVisite >= 2) {
            return SegmentoCrm::Attivo;
        }

        return SegmentoCrm::Nuovo;
    }

    private function aggiornaCliente(Cliente $cliente, float $valore90th): void
    {
        $commesseChiuse = $cliente->commesse()
            ->whereIn('stato', [
                StatoCommessa::Completata->value,
                StatoCommessa::Consegnata->value,
                StatoCommessa::Fatturata->value,
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $numeroVisite   = $commesseChiuse->count();
        $ultimaVisita   = $commesseChiuse->first()?->updated_at;

        // Calcola valore_lifetime: somma fatture pagate del cliente
        $valoreLifetime = Documento::where('cliente_id', $cliente->id)
            ->whereIn('stato', ['pagata', 'parzialmente_pagata', 'emessa', 'inviata_sdi', 'accettata_sdi'])
            ->sum('totale');

        $segmento = $this->calcolaSegmento(
            $numeroVisite,
            $ultimaVisita,
            (float) $valoreLifetime,
            $valore90th,
            $commesseChiuse,
            $cliente->created_at,
        );

        $cliente->updateQuietly([
            'valore_lifetime'  => $valoreLifetime,
            'numero_visite'    => $numeroVisite,
            'ultima_visita_at' => $ultimaVisita,
            'segmento_crm'     => $segmento->value,
        ]);
    }

    private function calcolaSegmento(
        int $numeroVisite,
        $ultimaVisita,
        float $valoreLifetime,
        float $valore90th,
        $commesse,
        $createdAt,
    ): SegmentoCrm {
        $oggi = now();

        // VIP: top 10% per valore lifetime oppure >= 10 visite nell'ultimo anno
        $visiteAnno = $commesse->filter(
            fn($c) => $c->updated_at && $c->updated_at->gte($oggi->copy()->subYear())
        )->count();

        if ($valore90th > 0 && $valoreLifetime >= $valore90th) {
            return SegmentoCrm::Vip;
        }
        if ($visiteAnno >= 10) {
            return SegmentoCrm::Vip;
        }

        // Perso: ultima visita > 24 mesi fa
        if ($ultimaVisita && $ultimaVisita->lt($oggi->copy()->subMonths(24))) {
            return SegmentoCrm::Perso;
        }

        // A rischio: ultima visita tra 12 e 24 mesi fa
        if ($ultimaVisita && $ultimaVisita->lt($oggi->copy()->subMonths(12))) {
            return SegmentoCrm::ARischio;
        }

        // Nuovo: meno di 2 commesse, prima visita negli ultimi 90 giorni
        if ($numeroVisite < 2 && $createdAt->gte($oggi->copy()->subDays(90))) {
            return SegmentoCrm::Nuovo;
        }

        // Attivo: ultima visita < 12 mesi, >= 2 commesse
        if ($ultimaVisita && $ultimaVisita->gte($oggi->copy()->subMonths(12)) && $numeroVisite >= 2) {
            return SegmentoCrm::Attivo;
        }

        // Default: nuovo (cliente senza visite o recente)
        return SegmentoCrm::Nuovo;
    }

    private function calcolaPercentile90($clienti = null): float
    {
        if ($clienti === null) {
            $clienti = Cliente::withTrashed(false)->get();
        }

        $valori = $clienti->pluck('valore_lifetime')
            ->map(fn($v) => (float) $v)
            ->filter(fn($v) => $v > 0)
            ->sort()
            ->values();

        if ($valori->isEmpty()) {
            return 0.0;
        }

        $index = (int) ceil(0.9 * $valori->count()) - 1;
        return $valori->get(max(0, $index), 0.0);
    }

    /**
     * Restituisce la distribuzione clienti per segmento.
     */
    public function distribuzione(): array
    {
        $rows = Cliente::withTrashed(false)
            ->select('segmento_crm', DB::raw('COUNT(*) as totale'))
            ->groupBy('segmento_crm')
            ->get();

        $result = [];
        foreach (SegmentoCrm::cases() as $s) {
            $result[$s->value] = 0;
        }
        $result['null'] = 0;

        foreach ($rows as $row) {
            $key = $row->segmento_crm ?? 'null';
            $result[$key] = $row->totale;
        }

        return $result;
    }

    /**
     * Clienti per segmento con consenso marketing = true.
     */
    public function clientiPerSegmento(string $segmento, ?array $filtriExtra = null)
    {
        $query = Cliente::withTrashed(false)
            ->where('consenso_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($segmento !== 'tutti') {
            $query->where('segmento_crm', $segmento);
        }

        if ($filtriExtra) {
            if (!empty($filtriExtra['ultima_visita_prima_di'])) {
                $query->where('ultima_visita_at', '<', $filtriExtra['ultima_visita_prima_di']);
            }
            if (!empty($filtriExtra['citta'])) {
                $query->where('citta', 'like', '%' . $filtriExtra['citta'] . '%');
            }
        }

        return $query;
    }
}
