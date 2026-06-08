<?php

namespace App\Jobs;

use App\Enums\StatoPneumatico;
use App\Models\NotificaLog;
use App\Models\Pneumatico;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InviaNotificaCambioStagionale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param int[] $pneumaticiIds */
    public function __construct(public readonly array $pneumaticiIds) {}

    public function handle(EmailTemplateService $templateService, MailConfigService $mailConfigService): void
    {
        if (! setting('notifiche_email_abilitato')) {
            return;
        }

        $mailConfigService->applica();

        $template = setting('template_email_cambio_stagionale', '');

        $pneumatici = Pneumatico::whereIn('id', $this->pneumaticiIds)
            ->where('stato', StatoPneumatico::InDeposito)
            ->with(['cliente', 'veicolo'])
            ->get();

        foreach ($pneumatici as $p) {
            $this->invia($p, $template, $templateService);
        }
    }

    private function invia(Pneumatico $p, string $template, EmailTemplateService $templateService): void
    {
        if (! $p->cliente?->email) return;

        // Idempotenza: nessuna notifica nelle ultime 24 ore per lo stesso set
        $recente = NotificaLog::where('pneumatico_id', $p->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($recente) return;

        $variabili = [
            '{{NOME_CLIENTE}}'      => $p->cliente->nome_completo,
            '{{TARGA}}'             => $p->veicolo->targa ?? '',
            '{{MISURA}}'            => $p->misura,
            '{{STAGIONE_DEPOSITO}}' => $p->stagione->label(),
            '{{NOME_OFFICINA}}'     => setting('officina_nome', ''),
            '{{TELEFONO_OFFICINA}}' => setting('officina_telefono', ''),
        ];

        ['oggetto' => $oggetto, 'corpo' => $corpo] = $templateService->compila($template, $variabili);

        try {
            Mail::to($p->cliente->email)->queue(
                new \App\Mail\NotificaGenerica($oggetto, $corpo)
            );

            NotificaLog::create([
                'tipo'          => 'email',
                'destinatario'  => $p->cliente->email,
                'oggetto'       => $oggetto,
                'corpo'         => $corpo,
                'stato'         => 'in_coda',
                'cliente_id'    => $p->cliente_id,
                'pneumatico_id' => $p->id,
            ]);

            $p->update(['notifica_inviata_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Errore notifica cambio stagionale pneumatico #' . $p->id . ': ' . $e->getMessage());
        }
    }
}
