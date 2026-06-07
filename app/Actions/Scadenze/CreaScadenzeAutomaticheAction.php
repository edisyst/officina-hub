<?php

namespace App\Actions\Scadenze;

use App\Enums\TipoScadenza;
use App\Models\Commessa;
use App\Models\Scadenza;

class CreaScadenzeAutomaticheAction
{
    // Parole chiave per identificare il tipo di intervento dalle righe commessa
    private const PAROLE_TAGLIANDO    = ['tagliando', 'cambio olio', 'filtro olio', 'olio motore', 'taglianda'];
    private const PAROLE_REVISIONE    = ['revisione'];

    /**
     * Analizza le righe della commessa e restituisce le scadenze suggerite
     * (senza salvarle — il Livewire le mostra nel modal di conferma).
     *
     * @return array<array{tipo: TipoScadenza, descrizione: string, data_scadenza: \Carbon\Carbon, km_scadenza: int|null}>
     */
    public function suggerisci(Commessa $commessa): array
    {
        $commessa->loadMissing('righe', 'veicolo', 'cliente');
        $suggerimenti = [];

        foreach ($commessa->righe as $riga) {
            $testo = strtolower($riga->descrizione ?? '');

            if ($this->contiene($testo, self::PAROLE_TAGLIANDO)) {
                $suggerimenti[] = [
                    'tipo'          => TipoScadenza::Tagliando,
                    'descrizione'   => 'Prossimo tagliando (generato automaticamente)',
                    'data_scadenza' => now()->addYear(),
                    'km_scadenza'   => $commessa->veicolo?->km_attuali
                        ? $commessa->veicolo->km_attuali + 15000
                        : null,
                ];
                break; // una sola scadenza tagliando per commessa
            }
        }

        foreach ($commessa->righe as $riga) {
            $testo = strtolower($riga->descrizione ?? '');

            if ($this->contiene($testo, self::PAROLE_REVISIONE)) {
                $suggerimenti[] = [
                    'tipo'          => TipoScadenza::Revisione,
                    'descrizione'   => 'Prossima revisione periodica (generata automaticamente)',
                    'data_scadenza' => now()->addYears(2),
                    'km_scadenza'   => null,
                ];
                break;
            }
        }

        return $suggerimenti;
    }

    /**
     * Salva le scadenze suggerite filtrate dall'utente (solo quelle non già esistenti).
     *
     * @param  array<int>  $tipiSelezionati  valori enum stringa selezionati dall'utente
     */
    public function salva(Commessa $commessa, array $suggerimenti, array $tipiSelezionati): void
    {
        $commessa->loadMissing('veicolo', 'cliente');

        foreach ($suggerimenti as $s) {
            $tipo = $s['tipo'] instanceof TipoScadenza ? $s['tipo'] : TipoScadenza::from($s['tipo']);

            if (! in_array($tipo->value, $tipiSelezionati, true)) {
                continue;
            }

            // Non creare duplicati: stessa tipologia per lo stesso veicolo nel futuro
            $giàEsiste = Scadenza::where('veicolo_id', $commessa->veicolo_id)
                ->where('tipo', $tipo->value)
                ->where('data_scadenza', '>=', now())
                ->exists();

            if ($giàEsiste) continue;

            Scadenza::create([
                'veicolo_id'              => $commessa->veicolo_id,
                'cliente_id'              => $commessa->cliente_id,
                'tipo'                    => $tipo,
                'descrizione'             => $s['descrizione'],
                'data_scadenza'           => $s['data_scadenza'],
                'km_scadenza'             => $s['km_scadenza'] ?? null,
                'km_attuali_al_momento'   => $commessa->veicolo?->km_attuali,
                'notifica_giorni_prima'   => (int) setting("promemoria_{$tipo->value}_giorni", 30),
                'commessa_origine_id'     => $commessa->id,
            ]);
        }
    }

    private function contiene(string $testo, array $parole): bool
    {
        foreach ($parole as $parola) {
            if (str_contains($testo, $parola)) return true;
        }
        return false;
    }
}
