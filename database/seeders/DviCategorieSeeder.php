<?php

namespace Database\Seeders;

use App\Models\DviCategoria;
use Illuminate\Database\Seeder;

class DviCategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categorie = [
            ['nome' => 'Freni',               'icona_css' => 'fas fa-circle-notch', 'ordinamento' => 1],
            ['nome' => 'Pneumatici',           'icona_css' => 'fas fa-circle',       'ordinamento' => 2],
            ['nome' => 'Luci',                 'icona_css' => 'fas fa-lightbulb',    'ordinamento' => 3],
            ['nome' => 'Motore',               'icona_css' => 'fas fa-cogs',         'ordinamento' => 4],
            ['nome' => 'Trasmissione',         'icona_css' => 'fas fa-sync-alt',     'ordinamento' => 5],
            ['nome' => 'Sospensioni',          'icona_css' => 'fas fa-car',          'ordinamento' => 6],
            ['nome' => 'Carrozzeria',          'icona_css' => 'fas fa-car-crash',    'ordinamento' => 7],
            ['nome' => 'Climatizzatore',       'icona_css' => 'fas fa-snowflake',    'ordinamento' => 8],
            ['nome' => 'Impianto elettrico',   'icona_css' => 'fas fa-bolt',         'ordinamento' => 9],
            ['nome' => 'Altro',                'icona_css' => 'fas fa-wrench',       'ordinamento' => 10],
        ];

        foreach ($categorie as $cat) {
            DviCategoria::firstOrCreate(
                ['nome' => $cat['nome']],
                array_merge($cat, ['attivo' => true])
            );
        }
    }
}
