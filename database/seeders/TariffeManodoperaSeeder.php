<?php

namespace Database\Seeders;

use App\Models\TariffaManodopera;
use Illuminate\Database\Seeder;

class TariffeManodoperaSeeder extends Seeder
{
    public function run(): void
    {
        $tariffe = [
            // Cambio olio e filtri
            ['codice' => 'MOD-001', 'descrizione' => 'Cambio olio motore (incluso smaltimento)', 'categoria' => 'Manutenzione ordinaria', 'minuti_standard' => 30, 'prezzo_listino' => 25.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'MOD-002', 'descrizione' => 'Sostituzione filtro olio', 'categoria' => 'Manutenzione ordinaria', 'minuti_standard' => 15, 'prezzo_listino' => 10.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'MOD-003', 'descrizione' => 'Sostituzione filtro aria motore', 'categoria' => 'Manutenzione ordinaria', 'minuti_standard' => 15, 'prezzo_listino' => 12.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'MOD-004', 'descrizione' => 'Sostituzione filtro abitacolo (antipolline)', 'categoria' => 'Manutenzione ordinaria', 'minuti_standard' => 20, 'prezzo_listino' => 15.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'MOD-005', 'descrizione' => 'Sostituzione filtro carburante', 'categoria' => 'Manutenzione ordinaria', 'minuti_standard' => 30, 'prezzo_listino' => 25.00, 'tipo_veicolo' => 'entrambi'],

            // Freni
            ['codice' => 'FRE-001', 'descrizione' => 'Sostituzione pastiglie freno anteriore', 'categoria' => 'Freni', 'minuti_standard' => 45, 'prezzo_listino' => 40.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'FRE-002', 'descrizione' => 'Sostituzione pastiglie freno posteriore', 'categoria' => 'Freni', 'minuti_standard' => 45, 'prezzo_listino' => 40.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'FRE-003', 'descrizione' => 'Sostituzione dischi freno anteriore', 'categoria' => 'Freni', 'minuti_standard' => 60, 'prezzo_listino' => 60.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'FRE-004', 'descrizione' => 'Sostituzione dischi freno posteriore', 'categoria' => 'Freni', 'minuti_standard' => 60, 'prezzo_listino' => 60.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'FRE-005', 'descrizione' => 'Spurgo impianto frenante', 'categoria' => 'Freni', 'minuti_standard' => 45, 'prezzo_listino' => 35.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'FRE-006', 'descrizione' => 'Sostituzione ganasce freno posteriore', 'categoria' => 'Freni', 'minuti_standard' => 90, 'prezzo_listino' => 70.00, 'tipo_veicolo' => 'auto'],

            // Distribuzione
            ['codice' => 'DIS-001', 'descrizione' => 'Sostituzione cinghia distribuzione', 'categoria' => 'Distribuzione', 'minuti_standard' => 180, 'prezzo_listino' => 150.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'DIS-002', 'descrizione' => 'Sostituzione kit distribuzione completo (cinghia + pompa acqua + tenditore)', 'categoria' => 'Distribuzione', 'minuti_standard' => 240, 'prezzo_listino' => 200.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'DIS-003', 'descrizione' => 'Sostituzione catena distribuzione', 'categoria' => 'Distribuzione', 'minuti_standard' => 300, 'prezzo_listino' => 280.00, 'tipo_veicolo' => 'entrambi'],

            // Sospensioni
            ['codice' => 'SOS-001', 'descrizione' => 'Sostituzione ammortizzatore anteriore (singolo)', 'categoria' => 'Sospensioni', 'minuti_standard' => 60, 'prezzo_listino' => 55.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'SOS-002', 'descrizione' => 'Sostituzione ammortizzatori anteriori (coppia)', 'categoria' => 'Sospensioni', 'minuti_standard' => 100, 'prezzo_listino' => 95.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'SOS-003', 'descrizione' => 'Sostituzione ammortizzatore posteriore (singolo)', 'categoria' => 'Sospensioni', 'minuti_standard' => 60, 'prezzo_listino' => 55.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'SOS-004', 'descrizione' => 'Sostituzione braccetto sterzo / testina', 'categoria' => 'Sospensioni', 'minuti_standard' => 60, 'prezzo_listino' => 50.00, 'tipo_veicolo' => 'auto'],

            // Frizione e trasmissione
            ['codice' => 'FRI-001', 'descrizione' => 'Sostituzione kit frizione (disco + spingidisco + cuscinetto)', 'categoria' => 'Frizione e trasmissione', 'minuti_standard' => 300, 'prezzo_listino' => 280.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'FRI-002', 'descrizione' => 'Sostituzione semiasse anteriore', 'categoria' => 'Frizione e trasmissione', 'minuti_standard' => 90, 'prezzo_listino' => 80.00, 'tipo_veicolo' => 'auto'],
            ['codice' => 'FRI-003', 'descrizione' => 'Sostituzione olio cambio manuale', 'categoria' => 'Frizione e trasmissione', 'minuti_standard' => 30, 'prezzo_listino' => 25.00, 'tipo_veicolo' => 'entrambi'],

            // Batteria ed elettrico
            ['codice' => 'ELE-001', 'descrizione' => 'Sostituzione batteria avviamento', 'categoria' => 'Elettrico', 'minuti_standard' => 20, 'prezzo_listino' => 18.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'ELE-002', 'descrizione' => 'Sostituzione alternatore', 'categoria' => 'Elettrico', 'minuti_standard' => 90, 'prezzo_listino' => 80.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'ELE-003', 'descrizione' => 'Sostituzione motorino di avviamento', 'categoria' => 'Elettrico', 'minuti_standard' => 90, 'prezzo_listino' => 80.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'ELE-004', 'descrizione' => 'Diagnosi elettronica (lettura e cancellazione errori)', 'categoria' => 'Elettrico', 'minuti_standard' => 30, 'prezzo_listino' => 30.00, 'tipo_veicolo' => 'entrambi'],

            // Candele e accensione
            ['codice' => 'ACC-001', 'descrizione' => 'Sostituzione candele (set 4 cilindri)', 'categoria' => 'Accensione', 'minuti_standard' => 40, 'prezzo_listino' => 35.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'ACC-002', 'descrizione' => 'Sostituzione bobine accensione', 'categoria' => 'Accensione', 'minuti_standard' => 45, 'prezzo_listino' => 40.00, 'tipo_veicolo' => 'entrambi'],

            // Pneumatici
            ['codice' => 'PNE-001', 'descrizione' => 'Montaggio e bilanciamento pneumatico (cad.)', 'categoria' => 'Pneumatici', 'minuti_standard' => 15, 'prezzo_listino' => 12.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'PNE-002', 'descrizione' => 'Revisione convergenza (assetto anteriore)', 'categoria' => 'Pneumatici', 'minuti_standard' => 45, 'prezzo_listino' => 40.00, 'tipo_veicolo' => 'auto'],

            // Raffreddamento
            ['codice' => 'RAF-001', 'descrizione' => 'Sostituzione liquido refrigerante', 'categoria' => 'Raffreddamento', 'minuti_standard' => 30, 'prezzo_listino' => 25.00, 'tipo_veicolo' => 'entrambi'],
            ['codice' => 'RAF-002', 'descrizione' => 'Sostituzione termostato', 'categoria' => 'Raffreddamento', 'minuti_standard' => 60, 'prezzo_listino' => 55.00, 'tipo_veicolo' => 'entrambi'],
        ];

        foreach ($tariffe as $tariffa) {
            TariffaManodopera::firstOrCreate(
                ['codice' => $tariffa['codice']],
                array_merge($tariffa, ['iva_percentuale' => 22.00, 'attivo' => true])
            );
        }
    }
}
