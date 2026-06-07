<?php

namespace App\Jobs;

use App\Models\DviIspezione;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InviaDviCliente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly DviIspezione $ispezione) {}

    public function handle(EmailTemplateService $templateService, MailConfigService $mailConfigService): void
    {
        $commessa = $this->ispezione->commessa;
        $cliente  = $commessa->cliente;
        $veicolo  = $commessa->veicolo;

        if (empty($cliente?->email)) {
            Log::warning('DVI: cliente senza email', ['ispezione_id' => $this->ispezione->id]);
            return;
        }

        $mailConfigService->applica();

        $linkDvi = url('/dvi/' . $this->ispezione->link_token);

        $variabili = [
            'NOME_CLIENTE'      => $cliente->nome_completo,
            'TARGA'             => $veicolo?->targa ?? '',
            'LINK_DVI'          => $linkDvi,
            'DATA_SCADENZA'     => $this->ispezione->link_scade_at->format('d/m/Y'),
            'NOME_OFFICINA'     => setting('officina_nome', 'Officina'),
            'TELEFONO_OFFICINA' => setting('officina_telefono', ''),
        ];

        $compilato = $templateService->compila('template_email_dvi', $variabili);

        try {
            Mail::raw($compilato['corpo'], function ($message) use ($cliente, $compilato) {
                $message->to($cliente->email, $cliente->nome_completo)
                        ->subject($compilato['oggetto'])
                        ->from(
                            setting('email_from_address', config('mail.from.address')),
                            setting('email_from_name', config('mail.from.name'))
                        );
            });

            Log::info('DVI inviata al cliente', [
                'ispezione_id' => $this->ispezione->id,
                'email'        => $cliente->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Errore invio DVI cliente', [
                'ispezione_id' => $this->ispezione->id,
                'errore'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
