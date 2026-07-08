<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RuoliSeeder::class,
            AdminSeeder::class,
            UtentiSeeder::class,
            SettingsSeeder::class,
            PontiSeeder::class,
            ClientiSeeder::class,
            VeicoliSeeder::class,
            CommesseSeeder::class,
            CategorieArticoliSeeder::class,
            CompagnieAssicurativeSeeder::class,
            DviCategorieSeeder::class,
            TariffeManodoperaSeeder::class,
            PacchettiServizioSeeder::class,
            CaseMadriSeeder::class,
            PricingSeeder::class,
        ]);
    }
}
