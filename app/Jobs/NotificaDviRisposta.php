<?php

namespace App\Jobs;

use App\Enums\StatoApprovazioneDvi;
use App\Models\DviIspezione;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificaDviRisposta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly DviIspezione $ispezione) {}

    public function handle(MailConfigService $mailConfigService): void
    {
        $emailOfficina = setting('officina_email');

        if (empty($emailOfficina)) {
            Log::warning('DVI: email officina non configurata per notifica risposta');
            return;
        }

        $mailConfigService->applica();

        $ispezione = $this->ispezione->load(['commessa.cliente', 'commessa.veicolo', 'voci']);
        $commessa  = $ispezione->commessa;
        $cliente   = $commessa->cliente;
        $veicolo   = $commessa->veicolo;

        $vociFiltrate = $ispezione->voci;
        $vociApprovate = $vociFiltrate->filter(
            fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Approvato
        );
        $vociRimandate = $vociFiltrate->filter(
            fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Rimandato
        );

        $importo = $vociApprovate->sum('prezzo_stimato');

        $linkCommessa = url('/commesse/' . $commessa->id);

        $righeApprovate = $vociApprovate->map(
            fn($v) => '✓ ' . $v->descrizione . ($v->prezzo_stimato ? ' (€ ' . number_format($v->prezzo_stimato, 2, ',', '.') . ')' : '')
        )->implode("\n");

        $righeRimandate = $vociRimandate->map(
            fn($v) => '→ ' . $v->descrizione
        )->implode("\n");

        $corpo = implode("\n", [
            'Il cliente ' . $cliente->nome_completo . ' ha risposto alla DVI per il veicolo ' . ($veicolo?->targa ?? '—') . '.',
            '',
            'Stato: ' . $ispezione->stato->label(),
            'Importo approvato: € ' . number_format($importo, 2, ',', '.'),
            '',
            $righeApprovate ? "INTERVENTI APPROVATI:\n" . $righeApprovate : '',
            $righeRimandate ? "\nINTERVENTI RIMANDATI:\n" . $righeRimandate : '',
            $ispezione->note_cliente ? "\nNote del cliente:\n" . $ispezione->note_cliente : '',
            '',
            'Vai alla commessa: ' . $linkCommessa,
        ]);

        $oggetto = 'DVI risposta ricevuta — ' . ($veicolo?->targa ?? 'N/D')
            . ' (' . $cliente->nome_completo . ')';

        try {
            Mail::raw($corpo, function ($message) use ($emailOfficina, $oggetto) {
                $message->to($emailOfficina)
                        ->subject($oggetto)
                        ->from(
                            setting('email_from_address', config('mail.from.address')),
                            setting('email_from_name', config('mail.from.name'))
                        );
            });

            Log::info('Notifica risposta DVI inviata officina', ['ispezione_id' => $ispezione->id]);
        } catch (\Throwable $e) {
            Log::error('Errore notifica risposta DVI', [
                'ispezione_id' => $ispezione->id,
                'errore'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
