<?php

namespace App\Jobs;

use App\Enums\StatoNotifica;
use App\Mail\NotificaRichiamo;
use App\Models\NotificaLog;
use App\Models\Scadenza;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InviaRichiamiScadenza implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(EmailTemplateService $templateService, MailConfigService $mailConfigService): void
    {
        if (! setting('notifiche_email_abilitato')) {
            return;
        }

        $mailConfigService->applica();

        // Seleziona scadenze candidate: non disabilitate, nel futuro, mai/raramente notificate
        $scadenze = Scadenza::query()
            ->with(['veicolo', 'cliente'])
            ->where('notifica_disabilitata', false)
            ->whereHas('cliente', fn($q) => $q->whereNotNull('email')->whereRaw("email != ''"))
            ->where('data_scadenza', '>=', now()->startOfDay())
            ->where(function ($q) {
                $q->whereNull('notifica_inviata_at')
                  ->orWhere('notifica_inviata_at', '<', now()->subDays(25));
            })
            ->get()
            // Filtra in PHP: data_scadenza <= oggi + notifica_giorni_prima (compatibile SQLite e MySQL)
            ->filter(fn($s) => $s->data_scadenza->lte(now()->startOfDay()->addDays($s->notifica_giorni_prima)));

        foreach ($scadenze as $scadenza) {
            $this->inviaRichiamo($scadenza, $templateService);
        }
    }

    private function inviaRichiamo(Scadenza $scadenza, EmailTemplateService $templateService): void
    {
        $cliente = $scadenza->cliente;
        $veicolo = $scadenza->veicolo;

        if (empty($cliente?->email)) return;

        // Idempotenza: non inviare se esiste già un log in_coda/inviata nelle ultime 24h
        $recente = NotificaLog::where('scadenza_id', $scadenza->id)
            ->whereIn('stato', [StatoNotifica::InCoda->value, StatoNotifica::Inviata->value])
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($recente) return;

        $variabili = [
            'NOME_CLIENTE'     => $cliente->nome_completo,
            'TARGA'            => $veicolo?->targa ?? '',
            'MARCA_MODELLO'    => trim(($veicolo?->marca ?? '') . ' ' . ($veicolo?->modello ?? '')),
            'TIPO_SCADENZA'    => $scadenza->tipo->label(),
            'DATA_SCADENZA'    => $scadenza->data_scadenza->format('d/m/Y'),
            'NOME_OFFICINA'    => setting('officina_nome', 'Officina'),
            'TELEFONO_OFFICINA' => setting('officina_telefono', ''),
        ];

        $compilato = $templateService->compila('template_email_richiamo_scadenza', $variabili);

        $log = NotificaLog::create([
            'tipo'        => 'email',
            'destinatario' => $cliente->email,
            'oggetto'     => $compilato['oggetto'],
            'corpo'       => $compilato['corpo'],
            'stato'       => StatoNotifica::InCoda,
            'scadenza_id' => $scadenza->id,
            'cliente_id'  => $cliente->id,
            'tentativi'   => 0,
        ]);

        try {
            Mail::to($cliente->email)
                ->send(new NotificaRichiamo($scadenza, $compilato['oggetto'], $compilato['corpo']));

            $log->update([
                'stato'      => StatoNotifica::Inviata,
                'inviata_at' => now(),
                'tentativi'  => 1,
            ]);

            $scadenza->update(['notifica_inviata_at' => now()]);

            Log::info("Richiamo scadenza inviato", ['scadenza_id' => $scadenza->id, 'email' => $cliente->email]);

        } catch (\Throwable $e) {
            $log->update([
                'stato'     => StatoNotifica::Fallita,
                'errore'    => $e->getMessage(),
                'tentativi' => 1,
            ]);

            Log::error("Errore invio richiamo scadenza", ['scadenza_id' => $scadenza->id, 'errore' => $e->getMessage()]);
        }
    }
}
