<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Veicolo;
use Illuminate\Database\Seeder;

class VeicoliSeeder extends Seeder
{
    public function run(): void
    {
        $veicoli = [
            [
                'cliente_email' => 'giovanni.rossi@email.it',
                'dati' => [
                    'tipo'                => 'auto',
                    'targa'               => 'AB123CD',
                    'marca'               => 'Fiat',
                    'modello'             => 'Panda',
                    'anno_immatricolazione' => 2018,
                    'alimentazione'       => 'benzina',
                    'colore'              => 'Bianco',
                    'km_attuali'          => 62000,
                    'note'                => 'Piccolo graffio porta anteriore destra',
                ],
            ],
            [
                'cliente_email' => 'giovanni.rossi@email.it',
                'dati' => [
                    'tipo'                => 'moto',
                    'targa'               => 'XX999YY',
                    'marca'               => 'Honda',
                    'modello'             => 'CB500',
                    'anno_immatricolazione' => 2020,
                    'alimentazione'       => 'benzina',
                    'colore'              => 'Rossa',
                    'km_attuali'          => 18500,
                ],
            ],
            [
                'cliente_email' => 'anna.verdi@email.it',
                'dati' => [
                    'tipo'                => 'auto',
                    'targa'               => 'GH456LM',
                    'marca'               => 'Volkswagen',
                    'modello'             => 'Golf',
                    'versione'            => '1.6 TDI',
                    'anno_immatricolazione' => 2021,
                    'alimentazione'       => 'diesel',
                    'colore'              => 'Grigio metallizzato',
                    'km_attuali'          => 41000,
                ],
            ],
            [
                'cliente_email' => 'carlo.esposito@email.it',
                'dati' => [
                    'tipo'                => 'auto',
                    'targa'               => 'NP789QR',
                    'marca'               => 'Toyota',
                    'modello'             => 'Yaris',
                    'versione'            => '1.5 Hybrid',
                    'anno_immatricolazione' => 2022,
                    'alimentazione'       => 'ibrido',
                    'colore'              => 'Blu',
                    'km_attuali'          => 22000,
                ],
            ],
            [
                'cliente_email' => 'info@trasportisud.it',
                'dati' => [
                    'tipo'                => 'furgone',
                    'targa'               => 'ST012UV',
                    'marca'               => 'Iveco',
                    'modello'             => 'Daily',
                    'versione'            => '35S14',
                    'anno_immatricolazione' => 2019,
                    'alimentazione'       => 'diesel',
                    'colore'              => 'Bianco',
                    'km_attuali'          => 134000,
                    'note'                => 'Flotta aziendale – revisione ogni 20.000 km',
                ],
            ],
            [
                'cliente_email' => 'info@trasportisud.it',
                'dati' => [
                    'tipo'                => 'furgone',
                    'targa'               => 'WX345YZ',
                    'marca'               => 'Mercedes-Benz',
                    'modello'             => 'Sprinter',
                    'versione'            => '314 CDI',
                    'anno_immatricolazione' => 2020,
                    'alimentazione'       => 'diesel',
                    'colore'              => 'Bianco',
                    'km_attuali'          => 98000,
                    'note'                => 'Flotta aziendale',
                ],
            ],
            [
                'cliente_email' => 'marta.lombardi@email.it',
                'dati' => [
                    'tipo'                => 'auto',
                    'targa'               => 'LM678NP',
                    'marca'               => 'Renault',
                    'modello'             => 'Clio',
                    'versione'            => '1.0 TCe',
                    'anno_immatricolazione' => 2023,
                    'alimentazione'       => 'benzina',
                    'colore'              => 'Nero',
                    'km_attuali'          => 8500,
                ],
            ],
            [
                'cliente_email' => 'amministrazione@ediltech.it',
                'dati' => [
                    'tipo'                => 'auto',
                    'targa'               => 'QR901ST',
                    'marca'               => 'BMW',
                    'modello'             => 'Serie 3',
                    'versione'            => '320d xDrive',
                    'anno_immatricolazione' => 2022,
                    'alimentazione'       => 'diesel',
                    'colore'              => 'Argento',
                    'km_attuali'          => 31000,
                    'note'                => 'Auto aziendale direzione',
                ],
            ],
        ];

        foreach ($veicoli as $item) {
            $cliente = Cliente::where('email', $item['cliente_email'])->first();
            if (!$cliente) {
                continue;
            }

            $veicolo = Veicolo::firstOrCreate(
                ['targa' => $item['dati']['targa']],
                array_merge($item['dati'], ['cliente_id' => $cliente->id])
            );

            if (!$cliente->veicoli()->where('veicolo_id', $veicolo->id)->exists()) {
                $cliente->veicoli()->attach($veicolo->id, [
                    'proprietario_attuale' => true,
                    'data_inizio'          => now(),
                ]);
            }
        }
    }
}
