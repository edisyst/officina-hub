<?php

namespace App\Services;

use App\Models\Articolo;
use App\Models\Fornitore;

class FatturaPAParser
{
    /**
     * Parsa un file XML FatturaPA (FPR12 o FPA12) e restituisce un array strutturato.
     * Gestisce sia fatture B2B (FPR12) che PA (FPA12).
     */
    public function parsaFatturaAcquisto(string $xmlPath): array
    {
        $content = file_get_contents($xmlPath);
        if ($content === false) {
            throw new \RuntimeException("Impossibile leggere il file: {$xmlPath}");
        }

        // Rimuove eventuali namespace prefix per semplicità
        $content = preg_replace('/(<\/?)(\w+):/', '$1', $content);

        $xml = new \SimpleXMLElement($content, LIBXML_NOERROR | LIBXML_NOWARNING);

        $cedente  = $xml->FatturaElettronicaHeader->CedentePrestatore ?? null;
        $documento = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento ?? null;

        if (! $cedente || ! $documento) {
            throw new \RuntimeException('Struttura XML FatturaPA non valida: sezioni obbligatorie mancanti.');
        }

        // Dati fornitore (cedente)
        $denominazione = (string) ($cedente->DatiAnagrafici->Anagrafica->Denominazione
            ?? $cedente->DatiAnagrafici->Anagrafica->Cognome . ' ' . $cedente->DatiAnagrafici->Anagrafica->Nome);
        $partitaIva   = (string) ($cedente->DatiAnagrafici->IdFiscaleIVA->IdCodice ?? '');
        $codiceFiscale = (string) ($cedente->DatiAnagrafici->CodiceFiscale ?? '');

        // Dati documento
        $numero         = (string) $documento->Numero;
        $dataFattura    = (string) $documento->Data;
        $imponibile     = 0.0;
        $ivaTotale      = 0.0;
        $totale         = 0.0;

        // Riepilogo IVA
        foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DatiRiepilogo ?? [] as $riepilogo) {
            $imponibile += (float) $riepilogo->ImponibileImporto;
            $ivaTotale  += (float) $riepilogo->Imposta;
        }
        $totale = $imponibile + $ivaTotale;

        // Righe
        $righe = [];
        foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DettaglioLinee ?? [] as $linea) {
            $righe[] = [
                'descrizione'      => (string) $linea->Descrizione,
                'quantita'         => (float) ($linea->Quantita ?? 1),
                'prezzo_unitario'  => (float) $linea->PrezzoUnitario,
                'iva_percentuale'  => (float) ($linea->AliquotaIVA ?? 22),
                'imponibile_riga'  => (float) $linea->PrezzoTotale,
                'articolo_id'      => null,
            ];
        }

        // Auto-match fornitore per P.IVA
        $fornitoreId     = null;
        $fornitoreWarn   = false;
        if ($partitaIva) {
            $fornitore = Fornitore::where('partita_iva', $partitaIva)->first();
            if ($fornitore) {
                $fornitoreId = $fornitore->id;
            } else {
                $fornitoreWarn = true;
            }
        }

        // Auto-match articoli per codice fornitore
        foreach ($righe as &$riga) {
            // Non c'è un campo standard per codice articolo in FatturaPA, ma alcuni valorizzano CodiceArticolo
        }
        unset($riga);

        return [
            'fornitore_id'     => $fornitoreId,
            'fornitore_warn'   => $fornitoreWarn,
            'denominazione'    => trim($denominazione),
            'partita_iva'      => $partitaIva,
            'codice_fiscale'   => $codiceFiscale,
            'numero_fattura'   => $numero,
            'data_fattura'     => $dataFattura,
            'imponibile'       => round($imponibile, 2),
            'iva_totale'       => round($ivaTotale, 2),
            'totale'           => round($totale, 2),
            'righe'            => $righe,
        ];
    }

    /**
     * Estrae le fatture da un file ZIP contenente più XML FatturaPA.
     * Restituisce un array di risultati parsaFatturaAcquisto().
     */
    public function parsaZip(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossibile aprire il file ZIP: {$zipPath}");
        }

        $tmpDir  = sys_get_temp_dir() . '/fatturapa_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $risultati = [];

        try {
            $zip->extractTo($tmpDir);
            $zip->close();

            foreach (glob("{$tmpDir}/*.xml") as $xmlFile) {
                try {
                    $risultati[] = $this->parsaFatturaAcquisto($xmlFile);
                } catch (\Throwable $e) {
                    $risultati[] = ['errore' => $e->getMessage(), 'file' => basename($xmlFile)];
                }
            }
        } finally {
            array_map('unlink', glob("{$tmpDir}/*"));
            @rmdir($tmpDir);
        }

        return $risultati;
    }
}
