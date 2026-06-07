<?php

namespace App\Livewire\Scadenziario;

use App\Actions\Scadenze\CreaScadenzeAutomaticheAction;
use App\Enums\StatoNotifica;
use App\Enums\TipoScadenza;
use App\Mail\NotificaRichiamo;
use App\Models\NotificaLog;
use App\Models\Scadenza;
use App\Services\EmailTemplateService;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class GestioneScadenze extends Component
{
    use WithPagination;

    // Filtri
    public string $filtraTipo = '';
    public string $filtraCliente = '';
    public int|null $filtraVeicoloId = null;
    public string $filtraStatoNotifica = '';
    public string $dataDa = '';
    public string $dataA = '';

    // Modal creazione/modifica
    public bool $modalAperto = false;
    public ?int $scadenzaId = null;
    public string $tipo = '';
    public string $descrizione = '';
    public string $dataScadenza = '';
    public string $kmScadenza = '';
    public int $notificaGiorniPrima = 30;
    public int $clienteId = 0;
    public int $veicoloId = 0;

    // Storico notifiche
    public ?int $storicoAperto = null;

    // Feedback
    public string $messaggio = '';
    public string $messaggioTipo = '';

    // Scadenze suggerite (modal conferma da CommessaObserver)
    public bool $modalScadenzeSuggerite = false;
    public int $commessaSuggeritaId = 0;
    public array $suggerimenti = [];
    public array $tipiSelezionati = [];

    protected function rules(): array
    {
        return [
            'tipo'               => 'required|in:' . implode(',', array_column(TipoScadenza::cases(), 'value')),
            'dataScadenza'       => 'required|date',
            'kmScadenza'         => 'nullable|integer|min:0',
            'notificaGiorniPrima' => 'required|integer|min:1|max:365',
            'clienteId'          => 'required|integer|exists:clienti,id',
            'veicoloId'          => 'required|integer|exists:veicoli,id',
        ];
    }

    #[Computed]
    public function scadenze()
    {
        $q = Scadenza::with(['veicolo', 'cliente', 'notificheLog'])
            ->whereNull('deleted_at');

        if ($this->filtraTipo) {
            $q->where('tipo', $this->filtraTipo);
        }

        if ($this->filtraCliente) {
            $q->whereHas('cliente', fn($c) =>
                $c->where('nome', 'like', "%{$this->filtraCliente}%")
                  ->orWhere('cognome', 'like', "%{$this->filtraCliente}%")
                  ->orWhere('ragione_sociale', 'like', "%{$this->filtraCliente}%")
            );
        }

        if ($this->filtraStatoNotifica === 'notificata') {
            $q->whereNotNull('notifica_inviata_at');
        } elseif ($this->filtraStatoNotifica === 'non_notificata') {
            $q->whereNull('notifica_inviata_at');
        } elseif ($this->filtraStatoNotifica === 'disabilitata') {
            $q->where('notifica_disabilitata', true);
        }

        if ($this->dataDa) {
            $q->where('data_scadenza', '>=', $this->dataDa);
        }

        if ($this->dataA) {
            $q->where('data_scadenza', '<=', $this->dataA);
        }

        return $q->orderBy('data_scadenza')->paginate(20);
    }

    #[Computed]
    public function tipi(): array
    {
        return TipoScadenza::cases();
    }

    public function apriModal(?int $id = null): void
    {
        $this->reset(['tipo', 'descrizione', 'dataScadenza', 'kmScadenza', 'clienteId', 'veicoloId']);
        $this->notificaGiorniPrima = 30;
        $this->scadenzaId = $id;

        if ($id) {
            $s = Scadenza::findOrFail($id);
            $this->tipo               = $s->tipo->value;
            $this->descrizione        = $s->descrizione ?? '';
            $this->dataScadenza       = $s->data_scadenza->format('Y-m-d');
            $this->kmScadenza         = (string) ($s->km_scadenza ?? '');
            $this->notificaGiorniPrima = $s->notifica_giorni_prima;
            $this->clienteId          = $s->cliente_id;
            $this->veicoloId          = $s->veicolo_id;
        }

        $this->modalAperto = true;
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'tipo'                 => $this->tipo,
            'descrizione'          => $this->descrizione ?: null,
            'data_scadenza'        => $this->dataScadenza,
            'km_scadenza'          => $this->kmScadenza !== '' ? (int) $this->kmScadenza : null,
            'notifica_giorni_prima' => $this->notificaGiorniPrima,
            'cliente_id'           => $this->clienteId,
            'veicolo_id'           => $this->veicoloId,
        ];

        if ($this->scadenzaId) {
            Scadenza::findOrFail($this->scadenzaId)->update($dati);
        } else {
            Scadenza::create($dati);
        }

        $this->modalAperto = false;
        $this->unsetComputedProperty('scadenze');
        $this->flash('Scadenza salvata.', 'success');
    }

    public function toggleNotifica(int $id): void
    {
        $s = Scadenza::findOrFail($id);
        $s->update(['notifica_disabilitata' => ! $s->notifica_disabilitata]);
        $this->unsetComputedProperty('scadenze');
    }

    public function inviaOra(int $id): void
    {
        if (! setting('notifiche_email_abilitato')) {
            $this->flash('Notifiche email non abilitate. Configurarle in Impostazioni → Email.', 'warning');
            return;
        }

        $scadenza = Scadenza::with(['veicolo', 'cliente'])->findOrFail($id);
        $cliente  = $scadenza->cliente;

        if (empty($cliente?->email)) {
            $this->flash('Il cliente non ha un indirizzo email valido.', 'danger');
            return;
        }

        app(MailConfigService::class)->applica();

        $variabili = [
            'NOME_CLIENTE'      => $cliente->nome_completo,
            'TARGA'             => $scadenza->veicolo?->targa ?? '',
            'MARCA_MODELLO'     => trim(($scadenza->veicolo?->marca ?? '') . ' ' . ($scadenza->veicolo?->modello ?? '')),
            'TIPO_SCADENZA'     => $scadenza->tipo->label(),
            'DATA_SCADENZA'     => $scadenza->data_scadenza->format('d/m/Y'),
            'NOME_OFFICINA'     => setting('officina_nome', 'Officina'),
            'TELEFONO_OFFICINA' => setting('officina_telefono', ''),
        ];

        $compilato = app(EmailTemplateService::class)->compila('template_email_richiamo_scadenza', $variabili);

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

            $log->update(['stato' => StatoNotifica::Inviata, 'inviata_at' => now(), 'tentativi' => 1]);
            $scadenza->update(['notifica_inviata_at' => now()]);

            $this->flash('Richiamo inviato a ' . $cliente->email, 'success');
        } catch (\Throwable $e) {
            $log->update(['stato' => StatoNotifica::Fallita, 'errore' => $e->getMessage(), 'tentativi' => 1]);
            $this->flash('Errore invio: ' . $e->getMessage(), 'danger');
        }

        $this->unsetComputedProperty('scadenze');
    }

    public function apriStorico(int $id): void
    {
        $this->storicoAperto = $this->storicoAperto === $id ? null : $id;
    }

    public function elimina(int $id): void
    {
        Scadenza::findOrFail($id)->delete();
        $this->unsetComputedProperty('scadenze');
        $this->flash('Scadenza eliminata.', 'info');
    }

    // Gestisce l'evento scadenze-suggerite dal CommessaObserver
    public function scadenzeSuggerite(int $commessa_id, array $suggerimenti): void
    {
        $this->commessaSuggeritaId = $commessa_id;
        $this->suggerimenti = $suggerimenti;
        $this->tipiSelezionati = array_column($suggerimenti, 'tipo');
        $this->modalScadenzeSuggerite = true;
    }

    public function confermaSuggerimenti(): void
    {
        if (empty($this->tipiSelezionati)) {
            $this->modalScadenzeSuggerite = false;
            return;
        }

        $commessa = \App\Models\Commessa::find($this->commessaSuggeritaId);
        if ($commessa) {
            $suggerimentiConCarbon = array_map(function ($s) {
                return array_merge($s, [
                    'data_scadenza' => \Carbon\Carbon::parse($s['data_scadenza']),
                    'tipo' => \App\Enums\TipoScadenza::from($s['tipo']),
                ]);
            }, $this->suggerimenti);

            app(CreaScadenzeAutomaticheAction::class)->salva($commessa, $suggerimentiConCarbon, $this->tipiSelezionati);
        }

        $this->modalScadenzeSuggerite = false;
        $this->unsetComputedProperty('scadenze');
        $this->flash(count($this->tipiSelezionati) . ' scadenze create.', 'success');
    }

    private function flash(string $messaggio, string $tipo): void
    {
        $this->messaggio = $messaggio;
        $this->messaggioTipo = $tipo;
    }

    public function updatedFiltraTipo(): void   { $this->resetPage(); }
    public function updatedFiltraCliente(): void { $this->resetPage(); }
    public function updatedFiltraStatoNotifica(): void { $this->resetPage(); }
    public function updatedDataDa(): void       { $this->resetPage(); }
    public function updatedDataA(): void        { $this->resetPage(); }

    public function render()
    {
        return view('livewire.scadenziario.gestione-scadenze')
            ->layout('layouts.app', ['title' => 'Scadenziario Richiami']);
    }
}
