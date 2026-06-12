<?php

namespace App\Actions\Fatturazione;

use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Enums\TipoEmissione;
use App\Enums\TipoRiga;
use App\Models\CasaMadre;
use App\Models\Commessa;
use App\Models\Documento;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GeneraFatturaGaranziaAction
{
    public function __construct(
        private readonly NumerazioneService $numerazione,
    ) {}

    /**
     * Genera le fatture per una commessa con righe in garanzia.
     * - 1 fattura al cliente (solo righe non in garanzia)
     * - 1 fattura per ogni casa madre coinvolta (righe in garanzia raggruppate per casa_madre_id)
     *
     * @return Documento[] array di documenti creati (cliente sempre primo)
     */
    public function execute(Commessa $commessa): array
    {
        $commessa->load(['cliente', 'righe.garanzia', 'righe.casaMadre', 'veicolo']);

        $righeGaranzia = $commessa->righe
            ->where('in_garanzia', true)
            ->where('tipo', '!=', TipoRiga::Nota->value);

        if ($righeGaranzia->isEmpty()) {
            throw new InvalidArgumentException('La commessa non ha righe in garanzia (tipo non-nota).');
        }

        return DB::transaction(function () use ($commessa, $righeGaranzia) {
            $anno     = now()->year;
            $tipo     = TipoDocumento::Fattura->value;
            $documenti = [];

            // ─── Fattura Cliente (righe non in garanzia) ─────────────────────
            $righeCliente = $commessa->righe
                ->where('in_garanzia', false)
                ->where('tipo', '!=', TipoRiga::Nota->value);

            $docCliente = $this->creaFatturaCliente($commessa, $righeCliente, $anno, $tipo);
            $documenti[] = $docCliente;

            // ─── Fatture Case Madri (raggruppate per casa_madre_id) ───────────
            $perCasaMadre = $righeGaranzia->groupBy('casa_madre_id');

            foreach ($perCasaMadre as $casaMadreId => $righe) {
                $casaMadre = $casaMadreId ? CasaMadre::find($casaMadreId) : null;
                $docCasaMadre = $this->creaFatturaCasaMadre(
                    $commessa, $righe, $casaMadre, $anno, $tipo, $docCliente->id
                );
                $documenti[] = $docCasaMadre;

            }

            return $documenti;
        });
    }

    private function creaFatturaCliente(Commessa $commessa, $righe, int $anno, string $tipo): Documento
    {
        $imponibile = $righe->sum(fn($r) => $r->imponibile);
        $ivaTotale  = $righe->sum(fn($r) => $r->iva);
        $totale     = $imponibile + $ivaTotale;

        $progressivo = $this->numerazione->prossimo($tipo, $anno);
        $numero      = $this->numerazione->formattaNumero($tipo, $anno, $progressivo);

        $doc = Documento::create([
            'tipo'                   => TipoDocumento::Fattura,
            'numero'                 => $numero,
            'anno'                   => $anno,
            'progressivo'            => $progressivo,
            'commessa_id'            => $commessa->id,
            'cliente_id'             => $commessa->cliente_id,
            'tipo_emissione'         => TipoEmissione::Cliente,
            'tipo_emissione_garanzia'=> 'cliente',
            'data_emissione'         => now()->toDateString(),
            'data_scadenza'          => now()->addDays(30)->toDateString(),
            'imponibile'             => round($imponibile, 2),
            'iva_totale'             => round($ivaTotale, 2),
            'totale'                 => round($totale, 2),
            'stato'                  => StatoDocumento::Bozza,
            'metodo_pagamento'       => null,
            'note'                   => 'Lavori a carico cliente — interventi in garanzia esclusi.',
        ]);

        foreach ($righe->values() as $idx => $riga) {
            $imp = (float) $riga->imponibile;
            $iva = (float) $riga->iva;
            $doc->righe()->create([
                'descrizione'        => $riga->descrizione,
                'unita_misura'       => 'pz',
                'quantita'           => $riga->quantita,
                'prezzo_unitario'    => $riga->prezzo_unitario,
                'sconto_percentuale' => $riga->sconto_percentuale,
                'iva_percentuale'    => $riga->iva_percentuale,
                'imponibile_riga'    => round($imp, 2),
                'iva_riga'           => round($iva, 2),
                'ordinamento'        => $idx,
            ]);
        }

        if ($righe->isEmpty()) {
            $doc->righe()->create([
                'descrizione'        => 'Nessun addebito — intervento completamente coperto da garanzia.',
                'unita_misura'       => 'pz',
                'quantita'           => 1,
                'prezzo_unitario'    => 0,
                'sconto_percentuale' => 0,
                'iva_percentuale'    => 22,
                'imponibile_riga'    => 0,
                'iva_riga'           => 0,
                'ordinamento'        => 0,
            ]);
        }

        return $doc;
    }

    private function creaFatturaCasaMadre(
        Commessa $commessa,
        $righe,
        ?CasaMadre $casaMadre,
        int $anno,
        string $tipo,
        int $docClienteId
    ): Documento {
        $imponibile = $righe->sum(fn($r) => $r->imponibile);
        $ivaTotale  = $righe->sum(fn($r) => $r->iva);
        $totale     = $imponibile + $ivaTotale;

        $progressivo = $this->numerazione->prossimo($tipo, $anno);
        $numero      = $this->numerazione->formattaNumero($tipo, $anno, $progressivo);

        $numeroPratiche = $righe
            ->map(fn($r) => $r->garanzia?->numero_pratica)
            ->filter()
            ->unique()
            ->implode(', ');

        $doc = Documento::create([
            'tipo'                   => TipoDocumento::Fattura,
            'numero'                 => $numero,
            'anno'                   => $anno,
            'progressivo'            => $progressivo,
            'commessa_id'            => $commessa->id,
            'cliente_id'             => $commessa->cliente_id,
            'casa_madre_id'          => $casaMadre?->id,
            'tipo_emissione'         => TipoEmissione::CasaMadre,
            'tipo_emissione_garanzia'=> 'casa_madre',
            'documento_correlato_id' => $docClienteId,
            'data_emissione'         => now()->toDateString(),
            'data_scadenza'          => now()->addDays(60)->toDateString(),
            'imponibile'             => round($imponibile, 2),
            'iva_totale'             => round($ivaTotale, 2),
            'totale'                 => round($totale, 2),
            'stato'                  => StatoDocumento::Bozza,
            'metodo_pagamento'       => null,
            'note'                   => trim(
                'Rimborso interventi in garanzia'
                . ($casaMadre ? ' — ' . $casaMadre->ragione_sociale : '')
                . ($numeroPratiche ? ' — Pratica: ' . $numeroPratiche : '')
            ),
        ]);

        foreach ($righe->values() as $idx => $riga) {
            $imp = (float) $riga->imponibile;
            $iva = (float) $riga->iva;

            $desc = $riga->descrizione;
            if ($riga->garanzia?->numero_pratica) {
                $desc .= ' [Pratica: ' . $riga->garanzia->numero_pratica . ']';
            }

            $doc->righe()->create([
                'descrizione'        => $desc,
                'unita_misura'       => 'pz',
                'quantita'           => $riga->quantita,
                'prezzo_unitario'    => $riga->prezzo_unitario,
                'sconto_percentuale' => $riga->sconto_percentuale,
                'iva_percentuale'    => $riga->iva_percentuale,
                'imponibile_riga'    => round($imp, 2),
                'iva_riga'           => round($iva, 2),
                'ordinamento'        => $idx,
            ]);
        }

        return $doc;
    }
}
