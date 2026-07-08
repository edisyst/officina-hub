<?php

namespace App\Services\Pricing;

use App\Models\MatricePrezzo;
use App\Models\MatricePrezzoScaglione;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bordi scaglione: costo_da inclusivo (>=), costo_a esclusivo (<).
 * L'ultimo scaglione ha costo_a = null (aperto, cattura tutto il resto).
 */
class MatricePrezzoService
{
    /**
     * Suggerisce il prezzo di vendita dato un costo.
     * Restituisce null se nessuna matrice attiva o costo zero/nullo.
     */
    public function suggestPrice(float|string|null $cost, ?MatricePrezzo $matrix = null): ?string
    {
        $cost = (float) $cost;
        if ($cost <= 0) {
            return null;
        }

        $matrix ??= MatricePrezzo::attive()->where('is_default', true)->with('scaglioni')->first();
        if (! $matrix) {
            return null;
        }

        $scaglione = $matrix->scaglioni
            ->first(function (MatricePrezzoScaglione $s) use ($cost) {
                $daOk = $cost >= (float) $s->costo_da;
                $aOk  = $s->costo_a === null || $cost < (float) $s->costo_a;
                return $daOk && $aOk;
            });

        if (! $scaglione) {
            return null;
        }

        $prezzo = $cost * (1 + (float) $scaglione->markup_percent / 100);

        $prezzo = match ($scaglione->arrotondamento) {
            '0.10' => ceil($prezzo / 0.10) * 0.10,
            '0.50' => ceil($prezzo / 0.50) * 0.50,
            '1.00' => ceil($prezzo / 1.00) * 1.00,
            default => $prezzo,
        };

        return number_format($prezzo, 2, '.', '');
    }

    /**
     * Valida che gli scaglioni siano contigui, senza buchi, senza overlap,
     * con un solo costo_a null in coda.
     *
     * @param array $scaglioni  array di ['costo_da'=>, 'costo_a'=>, 'markup_percent'=>, 'arrotondamento'=>]
     * @throws ValidationException
     */
    public function validateScaglioni(array $scaglioni): void
    {
        $errors = [];

        if (empty($scaglioni)) {
            throw ValidationException::withMessages(['scaglioni' => 'Almeno uno scaglione è richiesto.']);
        }

        usort($scaglioni, fn($a, $b) => (float) $a['costo_da'] <=> (float) $b['costo_da']);

        foreach ($scaglioni as $i => $s) {
            $da     = (float) $s['costo_da'];
            $a      = isset($s['costo_a']) && $s['costo_a'] !== '' && $s['costo_a'] !== null
                ? (float) $s['costo_a']
                : null;
            $markup = (float) ($s['markup_percent'] ?? 0);

            if ($da < 0) {
                $errors["scaglioni.{$i}.costo_da"] = "Scaglione " . ($i + 1) . ": costo_da non può essere negativo.";
            }

            if ($a !== null && $a <= $da) {
                $errors["scaglioni.{$i}.costo_a"] = "Scaglione " . ($i + 1) . ": costo_a deve essere maggiore di costo_da.";
            }

            if ($markup < 0) {
                $errors["scaglioni.{$i}.markup_percent"] = "Scaglione " . ($i + 1) . ": markup non può essere negativo.";
            }

            // Null solo nell'ultimo scaglione
            if ($a === null && $i < count($scaglioni) - 1) {
                $errors["scaglioni.{$i}.costo_a"] = "Scaglione " . ($i + 1) . ": solo l'ultimo scaglione può avere costo_a vuoto (aperto).";
            }

            // Contiguo col precedente
            if ($i > 0) {
                $prev = $scaglioni[$i - 1];
                $prevA = isset($prev['costo_a']) && $prev['costo_a'] !== '' && $prev['costo_a'] !== null
                    ? (float) $prev['costo_a']
                    : null;

                if ($prevA === null) {
                    $errors["scaglioni.{$i}.costo_da"] = "Scaglione " . ($i + 1) . ": scaglione precedente è già aperto, nessuno scaglione successivo consentito.";
                } elseif (abs($da - $prevA) > 0.001) {
                    $errors["scaglioni.{$i}.costo_da"] = "Scaglione " . ($i + 1) . ": costo_da ({$da}) deve essere uguale a costo_a dello scaglione precedente ({$prevA}) — nessun buco consentito.";
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Verifica che l'ultimo scaglione sia aperto
        $last = end($scaglioni);
        $lastA = isset($last['costo_a']) && $last['costo_a'] !== '' && $last['costo_a'] !== null
            ? (float) $last['costo_a']
            : null;
        if ($lastA !== null) {
            throw ValidationException::withMessages([
                'scaglioni' => "L'ultimo scaglione deve avere costo_a vuoto (aperto) per coprire tutti i costi superiori.",
            ]);
        }
    }

    /**
     * Imposta una matrice come default (transazionale, togli il flag dalle altre).
     */
    public function setDefault(MatricePrezzo $matrix): void
    {
        DB::transaction(function () use ($matrix) {
            MatricePrezzo::where('id', '!=', $matrix->id)->update(['is_default' => false]);
            $matrix->update(['is_default' => true]);
        });
    }
}
