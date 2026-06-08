<?php

namespace App\Services\LookupTarga;

/** Provider fittizio per sviluppo e test — non richiede API key */
class MockProvider implements LookupTargaContract
{
    public function cerca(string $targa): ?array
    {
        $targa = strtoupper(trim($targa));

        // Simula "non trovato" per targhe troppo corte
        if (strlen($targa) < 5) {
            return null;
        }

        // Dati generati deterministicamente dalla targa
        $marche = ['FIAT', 'VOLKSWAGEN', 'FORD', 'RENAULT', 'TOYOTA', 'BMW', 'MERCEDES'];
        $modelli = ['Panda', 'Golf', 'Focus', 'Clio', 'Yaris', 'Serie 3', 'Classe A'];
        $alimentazioni = ['benzina', 'diesel', 'ibrido', 'elettrico'];

        $idx = crc32($targa);
        $marca = $marche[abs($idx) % count($marche)];
        $modello = $modelli[abs($idx + 1) % count($modelli)];
        $alimentazione = $alimentazioni[abs($idx + 2) % count($alimentazioni)];
        $anno = 2010 + (abs($idx) % 14);

        return [
            'marca'                => $marca,
            'modello'              => $modello,
            'versione'             => null,
            'anno_immatricolazione' => $anno,
            'alimentazione'        => $alimentazione,
            'cilindrata'           => 1200 + (abs($idx) % 1800),
            'colore'               => null,
        ];
    }
}
