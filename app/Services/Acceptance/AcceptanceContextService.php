<?php

namespace App\Services\Acceptance;

use App\Services\LookupTarga\LookupTargaService;

class AcceptanceContextService
{
    public function packagesEnabled(): bool
    {
        return class_exists(\App\Models\PacchettoServizio::class);
    }

    public function plateLookupEnabled(): bool
    {
        return app(LookupTargaService::class)->isAbilitato();
    }
}
