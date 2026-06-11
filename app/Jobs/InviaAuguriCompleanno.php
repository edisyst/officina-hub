<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\NotificaLog;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InviaAuguriCompleanno implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(EmailTemplateService $templateService, MailConfigService $mailConfigService): void
    {
        if (!setting('notifiche_email_abilitato')) {
            return;
        }

        $mailConfigService->applica();

        $oggi   = now();
        $giorno = $oggi->day;
        $mese   = $oggi->month;

        // Trova clienti con compleanno oggi, consenso marketing, email valorizzata
        $clienti = Cliente::withTrashed(false)
            ->where('consenso_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('data_nascita')
            ->get()
            ->filter(fn($c) => $c->data_nascita->day === $giorno && $c->data_nascita->month === $mese);

        foreach ($clienti as $cliente) {
            // Idempotenza: non inviare se già inviato oggi
            $esistente = NotificaLog::where('cliente_id', $cliente->id)
                ->where('sottotipo', 'compleanno')
                ->whereDate('created_at', $oggi->toDateString())
                ->exists();

            if ($esistente) {
                continue;
            }

            $variabili = [
                'NOME_CLIENTE'       => $cliente->nome_completo,
                'NOME_OFFICINA'      => setting('officina_nome', 'Officina'),
                'TELEFONO_OFFICINA'  => setting('officina_telefono', ''),
                'SCONTO_COMPLEANNO'  => setting('sconto_compleanno_percentuale', '10'),
            ];

            $compilato = $templateService->compila('template_email_compleanno', $variabili);

            $log = NotificaLog::create([
                'tipo'        => 'email',
                'sottotipo'   => 'compleanno',
                'destinatario' => $cliente->email,
                'oggetto'     => $compilato['oggetto'],
                'corpo'       => $compilato['corpo'],
                'stato'       => 'in_coda',
                'cliente_id'  => $cliente->id,
                'tentativi'   => 0,
            ]);

            try {
                Mail::send([], [], function ($message) use ($cliente, $compilato) {
                    $message->to($cliente->email)
                        ->subject($compilato['oggetto'])
                        ->html(nl2br(e($compilato['corpo'])));
                });

                $log->update(['stato' => 'inviata', 'inviata_at' => now(), 'tentativi' => 1]);
                Log::info("Auguri compleanno inviati a cliente {$cliente->id}");
            } catch (\Throwable $e) {
                $log->update(['stato' => 'fallita', 'errore' => $e->getMessage(), 'tentativi' => 1]);
                Log::error("Errore auguri compleanno cliente {$cliente->id}", ['errore' => $e->getMessage()]);
            }
        }
    }
}
