<?php

namespace App\Jobs;

use App\Models\Documento;
use App\Services\SdiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InviaDocumentoSdi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Documento $documento) {}

    public function handle(SdiService $sdi): void
    {
        if (! config('sdi.abilitato')) {
            return;
        }
        // TODO: implementare dopo accreditamento AdE (vedere docs/sdi-diretto.md)
    }
}
