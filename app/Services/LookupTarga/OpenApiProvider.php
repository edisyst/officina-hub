<?php

namespace App\Services\LookupTarga;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenApiProvider implements LookupTargaContract
{
    public function __construct(private readonly string $apiKey, private readonly int $timeoutMs = 3000) {}

    public function cerca(string $targa): ?array
    {
        try {
            $response = Http::withHeaders(['x-api-key' => $this->apiKey])
                ->timeout($this->timeoutMs / 1000)
                ->get("https://api.openapi.it/car/{$targa}");

            if ($response->failed()) {
                Log::warning('LookupTarga OpenAPI: risposta non OK', [
                    'targa'  => $targa,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json('data', $response->json() ?? []);

            return [
                'marca'                => $data['make'] ?? $data['marca'] ?? '',
                'modello'              => $data['model'] ?? $data['modello'] ?? '',
                'versione'             => $data['version'] ?? $data['versione'] ?? null,
                'anno_immatricolazione' => isset($data['year']) ? (int) $data['year'] : null,
                'alimentazione'        => $data['fuel'] ?? $data['alimentazione'] ?? null,
                'cilindrata'           => isset($data['displacement']) ? (int) $data['displacement'] : null,
                'colore'               => $data['color'] ?? $data['colore'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('LookupTarga OpenAPI: errore connessione', [
                'targa'   => $targa,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
