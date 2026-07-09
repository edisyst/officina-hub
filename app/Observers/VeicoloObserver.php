<?php

namespace App\Observers;

use App\Models\Veicolo;
use App\Services\Recommendations\RecommendationEngineService;

class VeicoloObserver
{
    public function updated(Veicolo $veicolo): void
    {
        if ($veicolo->wasChanged('km_attuali')) {
            app(RecommendationEngineService::class)->refreshFor($veicolo);
        }
    }
}
