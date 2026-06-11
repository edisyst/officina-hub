<?php

namespace App\Jobs;

use App\Services\Crm\SegmentazioneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AggiornaPunteggiCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(SegmentazioneService $service): void
    {
        $aggiornati = $service->ricalcolaTutti();
        Log::info("AggiornaPunteggiCrm: aggiornati {$aggiornati} clienti.");
    }
}
