<?php

namespace Database\Seeders;

use App\Models\CompagniaAssicurativa;
use Illuminate\Database\Seeder;

class CompagnieAssicurativeSeeder extends Seeder
{
    public function run(): void
    {
        $compagnie = [
            ['nome' => 'Generali Italia S.p.A.',        'codice_abi' => '01030'],
            ['nome' => 'UnipolSai Assicurazioni S.p.A.', 'codice_abi' => '01031'],
            ['nome' => 'Allianz S.p.A.',                'codice_abi' => '01025'],
            ['nome' => 'Zurich Insurance plc',          'codice_abi' => '01024'],
            ['nome' => 'AXA Assicurazioni S.p.A.',      'codice_abi' => '01032'],
            ['nome' => 'Groupama Assicurazioni S.p.A.', 'codice_abi' => '01035'],
            ['nome' => 'Cattolica Assicurazioni S.p.A.','codice_abi' => '01033'],
            ['nome' => 'SACE S.p.A.',                   'codice_abi' => '01036'],
            ['nome' => 'Vittoria Assicurazioni S.p.A.', 'codice_abi' => '01037'],
            ['nome' => 'Reale Mutua Assicurazioni',     'codice_abi' => '01038'],
        ];

        foreach ($compagnie as $data) {
            CompagniaAssicurativa::firstOrCreate(['nome' => $data['nome']], $data);
        }
    }
}
