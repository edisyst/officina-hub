<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CommessaLog;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommesseSeeder extends Seeder
{
    public function run(): void
    {
        $accettatore = User::whereHas('roles', fn($q) => $q->where('name', 'accettatore'))->first();
        $meccanico   = User::whereHas('roles', fn($q) => $q->where('name', 'meccanico'))->first();
        $cassa       = User::whereHas('roles', fn($q) => $q->where('name', 'cassa'))->first();
        $admin       = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        $commesse = [
            // --- Bozza ---
            [
                'cliente_email' => 'marta.lombardi@email.it',
                'targa'         => 'LM678NP',
                'stato_finale'  => 'bozza',
                'dati' => [
                    'tipo'                  => 'tagliando',
                    'descrizione_cliente'   => 'Tagliando ordinario e controllo freni',
                    'data_ingresso'         => now()->subDays(1),
                    'data_uscita_prevista'  => now()->addDays(2),
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Tagliando completo',             'quantita' => 1, 'prezzo_unitario' => 90,  'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Kit filtri (aria, olio, abitat.)', 'quantita' => 1, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Olio motore 5W40 5L',            'quantita' => 1, 'prezzo_unitario' => 38,  'iva_percentuale' => 22],
                ],
            ],

            // --- Accettata ---
            [
                'cliente_email' => 'anna.verdi@email.it',
                'targa'         => 'GH456LM',
                'stato_finale'  => 'accettata',
                'dati' => [
                    'tipo'                  => 'meccanica',
                    'descrizione_cliente'   => 'Rumore sospetto asse anteriore sx, possibile cuscinetto',
                    'data_ingresso'         => now()->subDays(2),
                    'data_uscita_prevista'  => now()->addDays(1),
                    'km_ingresso'           => 41000,
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Diagnosi e sostituzione cuscinetto ant. sx', 'quantita' => 2, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Cuscinetto ruota ant. sx',                   'quantita' => 1, 'prezzo_unitario' => 65, 'iva_percentuale' => 22],
                ],
            ],

            // --- In Lavorazione ---
            [
                'cliente_email' => 'carlo.esposito@email.it',
                'targa'         => 'NP789QR',
                'stato_finale'  => 'in_lavorazione',
                'dati' => [
                    'tipo'                => 'meccanica',
                    'descrizione_cliente' => 'Spia motore accesa, perdita di potenza in salita',
                    'data_ingresso'       => now()->subDays(3),
                    'km_ingresso'         => 22000,
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Diagnosi elettronica',            'quantita' => 1,   'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'manodopera', 'descrizione' => 'Sostituzione iniettore n.3',      'quantita' => 1.5, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Iniettore Toyota Yaris 1.5',      'quantita' => 1,   'prezzo_unitario' => 120, 'iva_percentuale' => 22],
                ],
            ],

            // --- Sospesa ---
            [
                'cliente_email' => 'info@trasportisud.it',
                'targa'         => 'ST012UV',
                'stato_finale'  => 'sospesa',
                'nota_sospensione' => 'In attesa ricambio: pompa iniezione — ordine fornitore #PO-2026-0412',
                'dati' => [
                    'tipo'                => 'meccanica',
                    'descrizione_cliente' => 'Difficoltà avviamento a freddo e fumo nero allo scarico',
                    'data_ingresso'       => now()->subDays(6),
                    'km_ingresso'         => 134000,
                    'diagnosi_tecnica'    => 'Diagnosi: pompa iniezione usurata. Necessaria sostituzione.',
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Diagnosi avanzata sistema iniezione', 'quantita' => 1, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'manodopera', 'descrizione' => 'Smontaggio/rimontaggio pompa iniezione', 'quantita' => 3, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Pompa iniezione Iveco Daily 2.3 HPI',   'quantita' => 1, 'prezzo_unitario' => 480, 'iva_percentuale' => 22],
                ],
            ],

            // --- Completata ---
            [
                'cliente_email' => 'giovanni.rossi@email.it',
                'targa'         => 'AB123CD',
                'stato_finale'  => 'completata',
                'dati' => [
                    'tipo'                => 'carrozzeria',
                    'descrizione_cliente' => 'Riparazione ammaccatura e verniciatura cofano anteriore',
                    'data_ingresso'       => now()->subDays(10),
                    'km_ingresso'         => 62000,
                    'diagnosi_tecnica'    => 'Ammaccatura 15x8 cm, vernice danneggiata su 30% del cofano. Riparazione senza sostituzione.',
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Raddrizzatura lamiera cofano',    'quantita' => 2,   'prezzo_unitario' => 45,  'iva_percentuale' => 22],
                    ['tipo' => 'manodopera', 'descrizione' => 'Preparazione e verniciatura',     'quantita' => 3,   'prezzo_unitario' => 45,  'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Vernice base + trasparente',      'quantita' => 1,   'prezzo_unitario' => 85,  'iva_percentuale' => 22, 'sconto_percentuale' => 5],
                    ['tipo' => 'articolo',   'descrizione' => 'Stucco e primer',                 'quantita' => 1,   'prezzo_unitario' => 22,  'iva_percentuale' => 22],
                ],
            ],

            // --- Consegnata ---
            [
                'cliente_email' => 'amministrazione@ediltech.it',
                'targa'         => 'QR901ST',
                'stato_finale'  => 'consegnata',
                'dati' => [
                    'tipo'                => 'tagliando',
                    'descrizione_cliente' => 'Tagliando 30.000 km, sostituzione pastiglie e dischi ant.',
                    'data_ingresso'       => now()->subDays(14),
                    'data_consegna'       => now()->subDays(5),
                    'km_ingresso'         => 31000,
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Tagliando 30.000 km',              'quantita' => 1, 'prezzo_unitario' => 120, 'iva_percentuale' => 22],
                    ['tipo' => 'manodopera', 'descrizione' => 'Sostituzione dischi e pastiglie ant.', 'quantita' => 1.5, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Kit tagliando BMW 320d',            'quantita' => 1, 'prezzo_unitario' => 160, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Dischi freno ant. (coppia)',        'quantita' => 1, 'prezzo_unitario' => 145, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Pastiglie freno ant.',              'quantita' => 1, 'prezzo_unitario' => 55,  'iva_percentuale' => 22],
                ],
            ],

            // --- Fatturata ---
            [
                'cliente_email' => 'giovanni.rossi@email.it',
                'targa'         => 'XX999YY',
                'stato_finale'  => 'fatturata',
                'dati' => [
                    'tipo'                => 'meccanica',
                    'descrizione_cliente' => 'Revisione generale pre-stagione estiva',
                    'data_ingresso'       => now()->subDays(20),
                    'data_consegna'       => now()->subDays(12),
                    'km_ingresso'         => 18500,
                ],
                'righe' => [
                    ['tipo' => 'manodopera', 'descrizione' => 'Revisione generale motore',     'quantita' => 2, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'manodopera', 'descrizione' => 'Registrazione catena distribuz.','quantita' => 1, 'prezzo_unitario' => 45, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Candele NGK (set 4)',            'quantita' => 1, 'prezzo_unitario' => 38, 'iva_percentuale' => 22],
                    ['tipo' => 'articolo',   'descrizione' => 'Olio motore 10W40 4L',           'quantita' => 1, 'prezzo_unitario' => 32, 'iva_percentuale' => 22],
                ],
            ],
        ];

        $anno    = now()->year;
        $counter = Commessa::withTrashed()->where('numero', 'like', "COM-{$anno}-%")->count();

        foreach ($commesse as $item) {
            $cliente = Cliente::where('email', $item['cliente_email'])->first();
            $veicolo = Veicolo::where('targa', $item['targa'])->first();

            if (!$cliente || !$veicolo) {
                continue;
            }

            $counter++;
            $numero = "COM-{$anno}-" . str_pad($counter, 4, '0', STR_PAD_LEFT);

            $base = array_merge($item['dati'], [
                'numero'     => $numero,
                'cliente_id' => $cliente->id,
                'veicolo_id' => $veicolo->id,
                'stato'      => 'bozza',
                'user_id'    => $accettatore?->id ?? $admin->id,
            ]);

            $commessa = Commessa::create($base);

            // Righe
            foreach ($item['righe'] as $i => $riga) {
                $commessa->righe()->create(array_merge([
                    'sconto_percentuale' => 0,
                    'ordinamento'        => ($i + 1) * 10,
                ], $riga));
            }

            // Avanza lo stato fino a quello desiderato registrando i log
            $this->avanzaStato($commessa, $item['stato_finale'], $accettatore, $meccanico, $cassa, $admin, $item['nota_sospensione'] ?? null);
        }
    }

    private function avanzaStato(
        Commessa $commessa,
        string $statoFinale,
        ?User $accettatore,
        ?User $meccanico,
        ?User $cassa,
        User $admin,
        ?string $notaSospensione
    ): void {
        $flusso = [
            'bozza'          => null,
            'accettata'      => ['da' => 'bozza',         'user' => $accettatore ?? $admin, 'nota' => 'Accettazione cliente'],
            'in_lavorazione' => ['da' => 'accettata',     'user' => $meccanico   ?? $admin, 'nota' => 'Avvio lavorazione'],
            'sospesa'        => ['da' => 'in_lavorazione','user' => $meccanico   ?? $admin, 'nota' => null],
            'completata'     => ['da' => 'in_lavorazione','user' => $meccanico   ?? $admin, 'nota' => 'Lavorazione completata'],
            'consegnata'     => ['da' => 'completata',    'user' => $accettatore ?? $admin, 'nota' => 'Veicolo consegnato al cliente'],
            'fatturata'      => ['da' => 'consegnata',    'user' => $cassa       ?? $admin, 'nota' => 'Fattura emessa'],
        ];

        $percorso = ['bozza', 'accettata', 'in_lavorazione', 'completata', 'consegnata', 'fatturata'];

        // Per "sospesa" il percorso è speciale: bozza → accettata → in_lavorazione → sospesa
        if ($statoFinale === 'sospesa') {
            $percorso = ['bozza', 'accettata', 'in_lavorazione', 'sospesa'];
        }

        $indiceFine = array_search($statoFinale, $percorso);

        for ($i = 1; $i <= $indiceFine; $i++) {
            $stato = $percorso[$i];
            $step  = $flusso[$stato];
            $nota  = ($stato === 'sospesa') ? $notaSospensione : $step['nota'];

            CommessaLog::create([
                'commessa_id' => $commessa->id,
                'stato_da'    => $percorso[$i - 1],
                'stato_a'     => $stato,
                'user_id'     => $step['user']->id,
                'nota'        => $nota,
                'created_at'  => now(),
            ]);

            $update = ['stato' => $stato];
            if ($stato === 'consegnata') {
                $update['data_consegna'] = $commessa->data_consegna ?? now()->subDays(rand(3, 7));
            }

            $commessa->update($update);
        }
    }
}
