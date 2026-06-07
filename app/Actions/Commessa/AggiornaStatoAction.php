<?php

namespace App\Actions\Commessa;

use App\Enums\StatoCommessa;
use App\Enums\StatoNotifica;
use App\Mail\NotificaCommessa;
use App\Models\Commessa;
use App\Models\CommessaLog;
use App\Models\NotificaLog;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AggiornaStatoAction
{
    public function __construct(private EmailTemplateService $templateService) {}

    public function execute(Commessa $commessa, StatoCommessa $nuovoStato, User $utente, ?string $nota = null): void
    {
        if (! $commessa->puoTransireA($nuovoStato)) {
            throw ValidationException::withMessages([
                'stato' => "Transizione non ammessa da '{$commessa->stato->label()}' a '{$nuovoStato->label()}'.",
            ]);
        }

        $statoPrecedente = $commessa->stato;

        $aggiornamenti = ['stato' => $nuovoStato];

        if ($nuovoStato === StatoCommessa::Consegnata) {
            $aggiornamenti['data_consegna'] = now();
        }

        $commessa->update($aggiornamenti);

        CommessaLog::create([
            'commessa_id' => $commessa->id,
            'stato_da'    => $statoPrecedente->value,
            'stato_a'     => $nuovoStato->value,
            'user_id'     => $utente->id,
            'nota'        => $nota,
            'created_at'  => now(),
        ]);

        $this->accodaEmailSeNecessario($commessa, $nuovoStato);
    }

    private function accodaEmailSeNecessario(Commessa $commessa, StatoCommessa $nuovoStato): void
    {
        // Notifiche disabilitate o email cliente assente
        if (! setting('notifiche_email_abilitato')) return;

        $commessa->loadMissing('cliente', 'veicolo');
        $cliente = $commessa->cliente;

        if (empty($cliente?->email)) return;

        $templateKey = match($nuovoStato) {
            StatoCommessa::Accettata  => 'template_email_accettazione',
            StatoCommessa::Completata => 'template_email_completata',
            StatoCommessa::Consegnata => 'template_email_consegnata',
            default                   => null,
        };

        if ($templateKey === null) return;

        // Evita duplicati: non accodare se esiste già una notifica per questa commessa negli ultimi 5 minuti
        $duplicato = NotificaLog::where('commessa_id', $commessa->id)
            ->whereIn('stato', [StatoNotifica::InCoda->value, StatoNotifica::Inviata->value])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($duplicato) return;

        $veicolo = $commessa->veicolo;

        $variabili = [
            'NOME_CLIENTE'         => $cliente->nome_completo,
            'TARGA'                => $veicolo?->targa ?? '',
            'MARCA_MODELLO'        => trim(($veicolo?->marca ?? '') . ' ' . ($veicolo?->modello ?? '')),
            'NUMERO_COMMESSA'      => $commessa->numero,
            'DATA_INGRESSO'        => $commessa->data_ingresso?->format('d/m/Y') ?? '',
            'DATA_USCITA_PREVISTA' => $commessa->data_uscita_prevista?->format('d/m/Y') ?? 'da definire',
            'DESCRIZIONE_CLIENTE'  => $commessa->descrizione_cliente ?? '',
            'TOTALE_COMMESSA'      => number_format($commessa->totale_lordo, 2, ',', '.'),
            'NOME_OFFICINA'        => setting('officina_nome', 'Officina'),
            'EMAIL_OFFICINA'       => setting('officina_email', ''),
            'TELEFONO_OFFICINA'    => setting('officina_telefono', ''),
        ];

        $compilato = $this->templateService->compila($templateKey, $variabili);

        $log = NotificaLog::create([
            'tipo'        => 'email',
            'destinatario' => $cliente->email,
            'oggetto'     => $compilato['oggetto'],
            'corpo'       => $compilato['corpo'],
            'stato'       => StatoNotifica::InCoda,
            'commessa_id' => $commessa->id,
            'cliente_id'  => $cliente->id,
            'tentativi'   => 0,
        ]);

        Mail::to($cliente->email)
            ->queue(new NotificaCommessa($commessa, $compilato['oggetto'], $compilato['corpo']));

        // Aggiorna il log dopo aver accodato
        $log->update(['stato' => StatoNotifica::InCoda]);
    }
}
