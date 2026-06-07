<?php

namespace Database\Seeders;

use App\Models\CategoriaArticolo;
use Illuminate\Database\Seeder;

class CategorieArticoliSeeder extends Seeder
{
    public function run(): void
    {
        $categorie = [
            ['nome' => 'Ricambi Motore',  'ordinamento' => 1],
            ['nome' => 'Ricambi Freni',   'ordinamento' => 2],
            ['nome' => 'Lubrificanti',    'ordinamento' => 3],
            ['nome' => 'Elettronica',     'ordinamento' => 4],
            ['nome' => 'Carrozzeria',     'ordinamento' => 5],
            ['nome' => 'Consumabili',     'ordinamento' => 6],
        ];

        foreach ($categorie as $dati) {
            CategoriaArticolo::firstOrCreate(['nome' => $dati['nome']], $dati);
        }
    }
}
