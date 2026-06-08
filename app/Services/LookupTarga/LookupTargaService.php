<?php

namespace App\Services\LookupTarga;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LookupTargaService
{
    private ?LookupTargaContract $provider;

    public function __construct()
    {
        $this->provider = $this->buildProvider();
    }

    /**
     * Cerca i dati del veicolo dalla targa.
     * Ritorna null se il servizio è disabilitato, non configurato, o in caso di errore.
     */
    public function cerca(string $targa): ?array
    {
        if ($this->provider === null) {
            return null;
        }

        $targa = strtoupper(trim($targa));

        return Cache::remember("targa_{$targa}", now()->addDays(30), function () use ($targa) {
            return $this->provider->cerca($targa);
        });
    }

    /** Cerca senza cache — usato per il "Test connessione" in settings */
    public function cercaSenzaCache(string $targa): ?array
    {
        if ($this->provider === null) {
            return null;
        }

        return $this->provider->cerca(strtoupper(trim($targa)));
    }

    public function isAbilitato(): bool
    {
        return (bool) setting('lookup_targa_abilitato', false);
    }

    public function isAutoSearch(): bool
    {
        return (bool) setting('lookup_targa_auto_search', false);
    }

    private function buildProvider(): ?LookupTargaContract
    {
        if (! $this->isAbilitato()) {
            return null;
        }

        $providerName = setting('lookup_targa_provider', 'mock');
        $apiKey       = setting('lookup_targa_api_key', '');
        $timeoutMs    = (int) setting('lookup_targa_timeout_ms', 3000);

        return match($providerName) {
            'infotarga'    => new InfoTargaProvider($apiKey, $timeoutMs),
            'openapi'      => new OpenApiProvider($apiKey, $timeoutMs),
            'mock'         => new MockProvider(),
            default        => null,
        };
    }
}
