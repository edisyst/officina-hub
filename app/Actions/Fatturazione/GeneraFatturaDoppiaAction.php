<?php

namespace App\Actions\Fatturazione;

use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Enums\TipoEmissione;
use App\Models\Commessa;
use App\Models\Documento;
use App\Services\NumerazioneService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GeneraFatturaDoppiaAction
{
    public function __construct(
        private readonly NumerazioneService $numerazione,
    ) {}

    /**
     * Genera due fatture: una a carico del cliente e una a carico dell'assicurazione.
     * Prerequisiti: commessa con sinistro + perizia con importo_netto_liquidato valorizzato.
     *
     * @return array{0: Documento, 1: Documento} [doc_cliente, doc_assicurazione]
     */
    public function execute(Commessa $commessa): array
    {
        $commessa->load(['cliente', 'righe', 'sinistro.perizia', 'sinistro.compagniaAssicurativa']);

        $sinistro = $commessa->sinistro;
        $perizia  = $sinistro?->perizia;

        if (!$perizia || $perizia->importo_netto_liquidato === null) {
            throw new InvalidArgumentException('La commessa non ha una perizia con importo netto valorizzato.');
        }

        return DB::transaction(function () use ($commessa, $sinistro, $perizia) {
            $anno = now()->year;
            $tipo = TipoDocumento::Fattura->value;

            $imponibileTotale  = (float) $commessa->totale_imponibile;
            $ivaTotaleCommessa = (float) $commessa->totale_iva;
            $nettoLiquidato    = (float) $perizia->importo_netto_liquidato;

            // Aliquota IVA media (proporzione rispetto al totale commessa)
            $ivaRate = $imponibileTotale > 0
                ? $ivaTotaleCommessa / $imponibileTotale
                : 0.22;

            // ─── Fattura Assicurazione ────────────────────────────────────────────────
            $impAssicurazione = round($nettoLiquidato, 2);
            $ivaAssicurazione = round($nettoLiquidato * $ivaRate, 2);
            $totAssicurazione = $impAssicurazione + $ivaAssicurazione;

            $progAssicurazione = $this->numerazione->prossimo($tipo, $anno);
            $numAssicurazione  = $this->numerazione->formattaNumero($tipo, $anno, $progAssicurazione);

            $docAssicurazione = Documento::create([
                'tipo'             => TipoDocumento::Fattura,
                'numero'           => $numAssicurazione,
                'anno'             => $anno,
                'progressivo'      => $progAssicurazione,
                'commessa_id'      => $commessa->id,
                'cliente_id'       => $commessa->cliente_id,
                'sinistro_id'      => $sinistro->id,
                'tipo_emissione'   => TipoEmissione::Assicurazione,
                'data_emissione'   => now()->toDateString(),
                'data_scadenza'    => now()->addDays(30)->toDateString(),
                'imponibile'       => $impAssicurazione,
                'iva_totale'       => $ivaAssicurazione,
                'totale'           => $totAssicurazione,
                'stato'            => StatoDocumento::Bozza,
                'metodo_pagamento' => null,
                'note'             => "Sinistro n. {$sinistro->numero_sinistro} — Perizia: importo netto liquidato",
            ]);

            $docAssicurazione->righe()->create([
                'descrizione'        => "Lavori carrozzeria coperti da perizia — Sinistro n. {$sinistro->numero_sinistro}",
                'unita_misura'       => 'pz',
                'quantita'           => 1,
                'prezzo_unitario'    => $impAssicurazione,
                'sconto_percentuale' => 0,
                'iva_percentuale'    => round($ivaRate * 100, 2),
                'imponibile_riga'    => $impAssicurazione,
                'iva_riga'           => $ivaAssicurazione,
                'ordinamento'        => 0,
            ]);

            // ─── Fattura Cliente ──────────────────────────────────────────────────────
            // Chiama prossimo() DOPO aver inserito il documento assicurazione
            $impCliente = round($imponibileTotale - $impAssicurazione, 2);
            $ivaCliente = round($ivaTotaleCommessa - $ivaAssicurazione, 2);
            $totCliente = $impCliente + $ivaCliente;

            $progCliente = $this->numerazione->prossimo($tipo, $anno);
            $numCliente  = $this->numerazione->formattaNumero($tipo, $anno, $progCliente);

            $docCliente = Documento::create([
                'tipo'                   => TipoDocumento::Fattura,
                'numero'                 => $numCliente,
                'anno'                   => $anno,
                'progressivo'            => $progCliente,
                'commessa_id'            => $commessa->id,
                'cliente_id'             => $commessa->cliente_id,
                'sinistro_id'            => $sinistro->id,
                'tipo_emissione'         => TipoEmissione::Cliente,
                'documento_correlato_id' => $docAssicurazione->id,
                'data_emissione'         => now()->toDateString(),
                'data_scadenza'          => now()->addDays(30)->toDateString(),
                'imponibile'             => $impCliente,
                'iva_totale'             => $ivaCliente,
                'totale'                 => $totCliente,
                'stato'                  => StatoDocumento::Bozza,
                'metodo_pagamento'       => null,
                'note'                   => "Parte a carico cliente — Sinistro n. {$sinistro->numero_sinistro}",
            ]);

            $this->creaRigheCliente($docCliente, $commessa, $impCliente, $ivaRate);

            // Collegamento incrociato
            $docAssicurazione->update(['documento_correlato_id' => $docCliente->id]);

            return [$docCliente, $docAssicurazione];
        });
    }

    private function creaRigheCliente(Documento $doc, Commessa $commessa, float $impCliTotale, float $ivaRate): void
    {
        if ($commessa->righe->isEmpty()) {
            $doc->righe()->create([
                'descrizione'        => 'Lavori carrozzeria a carico cliente',
                'unita_misura'       => 'pz',
                'quantita'           => 1,
                'prezzo_unitario'    => $impCliTotale,
                'sconto_percentuale' => 0,
                'iva_percentuale'    => round($ivaRate * 100, 2),
                'imponibile_riga'    => $impCliTotale,
                'iva_riga'           => round($impCliTotale * $ivaRate, 2),
                'ordinamento'        => 0,
            ]);
            return;
        }

        foreach ($commessa->righe as $idx => $riga) {
            $imp = (float) $riga->quantita * (float) $riga->prezzo_unitario;
            $imp = $imp * (1 - (float) $riga->sconto_percentuale / 100);
            $iva = $imp * ((float) $riga->iva_percentuale / 100);

            $doc->righe()->create([
                'commessa_riga_id'   => $riga->id,
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

        // Riga di detrazione per la copertura assicurativa
        $detrazione = round((float) $commessa->totale_imponibile - $impCliTotale, 2);
        if ($detrazione > 0) {
            $doc->righe()->create([
                'descrizione'        => 'Detrazione copertura assicurativa',
                'unita_misura'       => 'pz',
                'quantita'           => 1,
                'prezzo_unitario'    => -$detrazione,
                'sconto_percentuale' => 0,
                'iva_percentuale'    => round($ivaRate * 100, 2),
                'imponibile_riga'    => -$detrazione,
                'iva_riga'           => round(-$detrazione * $ivaRate, 2),
                'ordinamento'        => $commessa->righe->count(),
            ]);
        }
    }
}
