<?php

namespace Database\Seeders;

use App\Models\MaintenanceRule;
use App\Models\Veicolo;
use App\Services\Recommendations\RecommendationEngineService;
use Illuminate\Database\Seeder;

class RecommendationSeeder extends Seeder
{
    public function run(): void
    {
        // Regole realistiche
        $rules = [
            ['name' => 'Tagliando olio',               'every_km' => 15000, 'every_months' => 12],
            ['name' => 'Filtro aria',                   'every_km' => 30000, 'every_months' => 24],
            ['name' => 'Cinghia di distribuzione',      'every_km' => 120000, 'every_months' => null],
            ['name' => 'Liquido freni',                 'every_km' => null,  'every_months' => 24],
        ];

        foreach ($rules as $rule) {
            MaintenanceRule::firstOrCreate(['name' => $rule['name']], array_merge($rule, ['is_active' => true]));
        }

        // Genera raccomandazioni di esempio sui veicoli demo
        $engine = app(RecommendationEngineService::class);
        Veicolo::take(3)->get()->each(fn($v) => $engine->refreshFor($v));
    }
}
