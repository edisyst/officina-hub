<?php

namespace App\Jobs;

use App\Enums\StatoCampagna;
use App\Models\CampagnaEmail;
use App\Models\CampagnaInvio;
use App\Services\Crm\SegmentazioneService;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InviaCampagnaEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $campagnaId) {}

    public function handle(
        SegmentazioneService $segmentazione,
        EmailTemplateService $templateService,
        MailConfigService $mailConfigService,
    ): void {
        $campagna = CampagnaEmail::find($this->campagnaId);

        if (!$campagna || $campagna->stato === StatoCampagna::Annullata) {
            return;
        }

        $campagna->update(['stato' => StatoCampagna::InInvio]);
        $mailConfigService->applica();

        $clienti = $segmentazione->clientiPerSegmento(
            $campagna->segmento_target,
            $campagna->filtro_json,
        )->get();

        $campagna->update(['totale_destinatari' => $clienti->count()]);

        $inviati = 0;
        $errori  = 0;

        foreach ($clienti as $cliente) {
            // GDPR: mai inviare senza consenso
            if (!$cliente->consenso_marketing) {
                throw new \RuntimeException(
                    "Tentativo invio campagna {$campagna->id} a cliente {$cliente->id} senza consenso marketing."
                );
            }

            // Idempotenza: non reinviare se già inviata
            $esistente = CampagnaInvio::where('campagna_email_id', $campagna->id)
                ->where('cliente_id', $cliente->id)
                ->where('stato', 'inviata')
                ->exists();

            if ($esistente) {
                continue;
            }

            $invio = CampagnaInvio::firstOrCreate(
                [
                    'campagna_email_id' => $campagna->id,
                    'cliente_id'        => $cliente->id,
                ],
                ['stato' => 'in_coda']
            );

            if ($invio->stato === 'inviata') {
                continue;
            }

            $variabili = [
                'NOME_CLIENTE'      => $cliente->nome_completo,
                'NOME_OFFICINA'     => setting('officina_nome', 'Officina'),
                'TELEFONO_OFFICINA' => setting('officina_telefono', ''),
                'EMAIL_OFFICINA'    => setting('officina_email', ''),
            ];

            // Aggiungi prima targa veicolo se disponibile
            $veicolo = $cliente->veicoli()->first();
            $variabili['TARGA_VEICOLO'] = $veicolo?->targa ?? '';

            $compilato = $templateService->compilaManuale(
                $campagna->oggetto,
                $campagna->corpo,
                $variabili,
            );

            try {
                Mail::send([], [], function ($message) use ($cliente, $compilato) {
                    $message->to($cliente->email)
                        ->subject($compilato['oggetto'])
                        ->html(nl2br(e($compilato['corpo'])));
                });

                $invio->update(['stato' => 'inviata', 'inviata_at' => now()]);
                $inviati++;
            } catch (\Throwable $e) {
                $invio->update(['stato' => 'fallita', 'errore' => $e->getMessage()]);
                $errori++;
                Log::error("InviaCampagnaEmail: errore cliente {$cliente->id}", ['errore' => $e->getMessage()]);
            }
        }

        $campagna->update([
            'stato'          => StatoCampagna::Completata,
            'inviata_at'     => now(),
            'totale_inviati' => $inviati,
            'totale_errori'  => $errori,
        ]);

        Log::info("Campagna {$campagna->id} completata: {$inviati} inviati, {$errori} errori.");
    }
}
