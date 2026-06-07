<?php

namespace Database\Seeders;

use App\Models\Ponte;
use Illuminate\Database\Seeder;

class PontiSeeder extends Seeder
{
    public function run(): void
    {
        $ponti = [
            ['nome' => 'Ponte 1',        'tipo' => 'meccanica',   'ordinamento' => 1],
            ['nome' => 'Ponte 2',        'tipo' => 'meccanica',   'ordinamento' => 2],
            ['nome' => 'Box Carrozzeria','tipo' => 'carrozzeria', 'ordinamento' => 3],
        ];

        foreach ($ponti as $data) {
            Ponte::firstOrCreate(['nome' => $data['nome']], $data);
        }
    }
}
