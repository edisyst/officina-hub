<?php

namespace Database\Seeders;

use App\Models\MatricePrezzo;
use App\Models\TariffaOraria;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        // Matrice di esempio realistica
        $matrice = MatricePrezzo::firstOrCreate(
            ['nome' => 'Standard'],
            ['is_default' => true, 'is_attiva' => true]
        );

        if ($matrice->scaglioni()->count() === 0) {
            $matrice->scaglioni()->createMany([
                ['costo_da' =>   0.00, 'costo_a' =>  10.00, 'markup_percent' => 100.00, 'arrotondamento' => '0.50'],
                ['costo_da' =>  10.00, 'costo_a' =>  50.00, 'markup_percent' =>  70.00, 'arrotondamento' => '0.50'],
                ['costo_da' =>  50.00, 'costo_a' => 200.00, 'markup_percent' =>  50.00, 'arrotondamento' => '0.50'],
                ['costo_da' => 200.00, 'costo_a' =>   null, 'markup_percent' =>  35.00, 'arrotondamento' => '1.00'],
            ]);
        }

        // Tariffe orarie
        $tariffe = [
            ['nome' => 'Meccanica',   'tariffa_oraria' => 75.00, 'is_default' => true],
            ['nome' => 'Elettrauto',  'tariffa_oraria' => 85.00, 'is_default' => false],
            ['nome' => 'Carrozzeria', 'tariffa_oraria' => 65.00, 'is_default' => false],
        ];

        foreach ($tariffe as $t) {
            TariffaOraria::firstOrCreate(['nome' => $t['nome']], array_merge($t, ['is_attiva' => true]));
        }
    }
}
