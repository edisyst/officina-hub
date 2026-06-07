<?php

namespace Database\Seeders;

use App\Models\PacchettoRiga;
use App\Models\PacchettoServizio;
use App\Models\TariffaManodopera;
use Illuminate\Database\Seeder;

class PacchettiServizioSeeder extends Seeder
{
    public function run(): void
    {
        // Tagliando completo benzina
        $tagliandoBenzina = PacchettoServizio::firstOrCreate(
            ['nome' => 'Tagliando completo benzina'],
            [
                'descrizione'   => 'Tagliando completo per motori benzina: cambio olio, filtri, candele e controllo generale.',
                'tipo_commessa' => 'entrambi',
                'tipo_veicolo'  => 'auto',
                'alimentazione' => 'benzina',
                'attivo'        => true,
                'ordinamento'   => 1,
            ]
        );

        if ($tagliandoBenzina->righe()->count() === 0) {
            $mod001 = TariffaManodopera::where('codice', 'MOD-001')->first();
            $mod002 = TariffaManodopera::where('codice', 'MOD-002')->first();
            $mod003 = TariffaManodopera::where('codice', 'MOD-003')->first();
            $acc001 = TariffaManodopera::where('codice', 'ACC-001')->first();

            $righe = [
                ['tipo' => 'manodopera', 'descrizione' => $mod001?->descrizione ?? 'Cambio olio motore', 'quantita' => 0.5, 'prezzo_unitario' => $mod001?->prezzo_listino ?? 25.00, 'ordinamento' => 1],
                ['tipo' => 'manodopera', 'descrizione' => $mod002?->descrizione ?? 'Sostituzione filtro olio', 'quantita' => 0.25, 'prezzo_unitario' => $mod002?->prezzo_listino ?? 10.00, 'ordinamento' => 2],
                ['tipo' => 'manodopera', 'descrizione' => $mod003?->descrizione ?? 'Sostituzione filtro aria', 'quantita' => 0.25, 'prezzo_unitario' => $mod003?->prezzo_listino ?? 12.00, 'ordinamento' => 3],
                ['tipo' => 'manodopera', 'descrizione' => $acc001?->descrizione ?? 'Sostituzione candele', 'quantita' => 0.67, 'prezzo_unitario' => $acc001?->prezzo_listino ?? 35.00, 'ordinamento' => 4],
                ['tipo' => 'nota', 'descrizione' => 'Controllo visivo generale incluso', 'quantita' => 1, 'prezzo_unitario' => 0, 'ordinamento' => 5],
            ];

            foreach ($righe as $riga) {
                $tagliandoBenzina->righe()->create(array_merge(['iva_percentuale' => 22, 'sconto_percentuale' => 0], $riga));
            }
        }

        // Tagliando completo diesel
        $tagliandoDiesel = PacchettoServizio::firstOrCreate(
            ['nome' => 'Tagliando completo diesel'],
            [
                'descrizione'   => 'Tagliando completo per motori diesel: cambio olio, filtri olio, aria e carburante.',
                'tipo_commessa' => 'entrambi',
                'tipo_veicolo'  => 'auto',
                'alimentazione' => 'diesel',
                'attivo'        => true,
                'ordinamento'   => 2,
            ]
        );

        if ($tagliandoDiesel->righe()->count() === 0) {
            $mod001 = TariffaManodopera::where('codice', 'MOD-001')->first();
            $mod002 = TariffaManodopera::where('codice', 'MOD-002')->first();
            $mod003 = TariffaManodopera::where('codice', 'MOD-003')->first();
            $mod005 = TariffaManodopera::where('codice', 'MOD-005')->first();

            $righe = [
                ['tipo' => 'manodopera', 'descrizione' => $mod001?->descrizione ?? 'Cambio olio motore', 'quantita' => 0.5, 'prezzo_unitario' => $mod001?->prezzo_listino ?? 25.00, 'ordinamento' => 1],
                ['tipo' => 'manodopera', 'descrizione' => $mod002?->descrizione ?? 'Sostituzione filtro olio', 'quantita' => 0.25, 'prezzo_unitario' => $mod002?->prezzo_listino ?? 10.00, 'ordinamento' => 2],
                ['tipo' => 'manodopera', 'descrizione' => $mod003?->descrizione ?? 'Sostituzione filtro aria', 'quantita' => 0.25, 'prezzo_unitario' => $mod003?->prezzo_listino ?? 12.00, 'ordinamento' => 3],
                ['tipo' => 'manodopera', 'descrizione' => $mod005?->descrizione ?? 'Sostituzione filtro carburante', 'quantita' => 0.5, 'prezzo_unitario' => $mod005?->prezzo_listino ?? 25.00, 'ordinamento' => 4],
            ];

            foreach ($righe as $riga) {
                $tagliandoDiesel->righe()->create(array_merge(['iva_percentuale' => 22, 'sconto_percentuale' => 0], $riga));
            }
        }

        // Kit freni anteriori
        $kitFreni = PacchettoServizio::firstOrCreate(
            ['nome' => 'Kit freni anteriori completo'],
            [
                'descrizione'   => 'Sostituzione dischi e pastiglie freno anteriore con spurgo.',
                'tipo_commessa' => 'entrambi',
                'tipo_veicolo'  => 'auto',
                'alimentazione' => 'tutte',
                'attivo'        => true,
                'ordinamento'   => 3,
            ]
        );

        if ($kitFreni->righe()->count() === 0) {
            $fre001 = TariffaManodopera::where('codice', 'FRE-001')->first();
            $fre003 = TariffaManodopera::where('codice', 'FRE-003')->first();

            $righe = [
                ['tipo' => 'manodopera', 'descrizione' => $fre003?->descrizione ?? 'Sostituzione dischi freno anteriore', 'quantita' => 1, 'prezzo_unitario' => $fre003?->prezzo_listino ?? 60.00, 'ordinamento' => 1],
                ['tipo' => 'manodopera', 'descrizione' => $fre001?->descrizione ?? 'Sostituzione pastiglie freno anteriore', 'quantita' => 0.75, 'prezzo_unitario' => $fre001?->prezzo_listino ?? 40.00, 'ordinamento' => 2],
            ];

            foreach ($righe as $riga) {
                $kitFreni->righe()->create(array_merge(['iva_percentuale' => 22, 'sconto_percentuale' => 0], $riga));
            }
        }
    }
}
