<?php

namespace App\Services\LookupTarga;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfoTargaProvider implements LookupTargaContract
{
    public function __construct(private readonly string $apiKey, private readonly int $timeoutMs = 3000) {}

    public function cerca(string $targa): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutMs / 1000)
                ->get("https://infotarga.com/api/v1/veicolo/{$targa}");

            if ($response->failed()) {
                Log::warning('LookupTarga InfoTarga: risposta non OK', [
                    'targa'  => $targa,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            return [
                'marca'                => $data['marca'] ?? '',
                'modello'              => $data['modello'] ?? '',
                'versione'             => $data['versione'] ?? null,
                'anno_immatricolazione' => isset($data['anno']) ? (int) $data['anno'] : null,
                'alimentazione'        => $data['alimentazione'] ?? null,
                'cilindrata'           => isset($data['cilindrata']) ? (int) $data['cilindrata'] : null,
                'colore'               => $data['colore'] ?? null,
            ];
        } catch (\Throwable $e) {
            // Non loggare la API key — è già nell'header Authorization gestito da Http::withToken
            Log::warning('LookupTarga InfoTarga: errore connessione', [
                'targa'   => $targa,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
