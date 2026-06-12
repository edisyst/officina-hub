<?php

namespace Database\Seeders;

use App\Models\CasaMadre;
use Illuminate\Database\Seeder;

class CaseMadriSeeder extends Seeder
{
    public function run(): void
    {
        $caseMadri = [
            [
                'ragione_sociale'       => 'FCA Italy S.p.A.',
                'partita_iva'           => '00536750164',
                'codice_destinatario_sdi'=> '0000000',
                'email'                 => 'garanzie@fca.com',
                'codice_convenzionamento'=> null,
                'note'                  => 'Fiat, Alfa Romeo, Lancia, Abarth',
            ],
            [
                'ragione_sociale'       => 'BMW Group Italia S.p.A.',
                'partita_iva'           => '01598440154',
                'codice_destinatario_sdi'=> '0000000',
                'email'                 => 'garanzie@bmw.it',
                'codice_convenzionamento'=> null,
                'note'                  => 'BMW, MINI',
            ],
            [
                'ragione_sociale'       => 'Mercedes-Benz Italia S.p.A.',
                'partita_iva'           => '00475660581',
                'codice_destinatario_sdi'=> '0000000',
                'email'                 => 'garanzie@mercedes-benz.it',
                'codice_convenzionamento'=> null,
                'note'                  => 'Mercedes-Benz, Smart',
            ],
            [
                'ragione_sociale'       => 'Volkswagen Group Italia S.p.A.',
                'partita_iva'           => '00827330150',
                'codice_destinatario_sdi'=> '0000000',
                'email'                 => 'garanzie@vw-group.it',
                'codice_convenzionamento'=> null,
                'note'                  => 'Volkswagen, Audi, SEAT, ŠKODA, Cupra',
            ],
            [
                'ragione_sociale'       => 'Renault Italia S.p.A.',
                'partita_iva'           => '00411700580',
                'codice_destinatario_sdi'=> '0000000',
                'email'                 => 'garanzie@renault.it',
                'codice_convenzionamento'=> null,
                'note'                  => 'Renault, Dacia',
            ],
        ];

        foreach ($caseMadri as $data) {
            CasaMadre::firstOrCreate(
                ['ragione_sociale' => $data['ragione_sociale']],
                $data
            );
        }
    }
}
