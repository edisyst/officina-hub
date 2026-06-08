<?php

namespace App\Livewire\Pneumatici;

use App\Enums\AzioneDeposito;
use App\Enums\StagionePneumatico;
use App\Enums\StatoPneumatico;
use App\Models\Commessa;
use App\Models\DepositoPneumatico;
use App\Models\Pneumatico;
use App\Models\Veicolo;
use Livewire\Component;
use Livewire\WithFileUploads;

class AccettazioneDeposito extends Component
{
    use WithFileUploads;

    public ?int $commessaId = null;

    // Ricerca veicolo
    public string $targaRicerca = '';
    public ?int   $veicoloId    = null;
    public ?int   $clienteId    = null;

    // Azione selezionata
    public string $azioneAttiva = '';  // deposita|monta|nuovo|smaltisci

    // Comune
    public ?int $pneumaticoSelezionatoId = null;

    // Form deposita
    public string $ubicazione         = '';
    public int    $usura_percentuale  = 0;
    public string $usura_note         = '';
    public string $km_deposito        = '';
    public string $note_deposito      = '';

    // Form monta da deposito
    public string $km_monta = '';

    // Form nuovo set
    public string $stagione_nuovo     = 'invernale';
    public string $marca_nuovo        = '';
    public string $misura_nuovo       = '';
    public string $note_nuovo         = '';

    public bool $operazioneCompletata = false;
    public string $messaggioEsito     = '';

    public function mount(?int $commessaId = null): void
    {
        $this->commessaId = $commessaId;

        if ($commessaId) {
            $commessa = Commessa::with(['veicolo', 'cliente'])->findOrFail($commessaId);
            if ($commessa->veicolo) {
                $this->veicoloId    = $commessa->veicolo->id;
                $this->clienteId    = $commessa->cliente_id;
                $this->targaRicerca = $commessa->veicolo->targa ?? '';
            }
        }
    }

    public function cercaVeicolo(): void
    {
        $this->veicoloId  = null;
        $this->clienteId  = null;
        $this->azioneAttiva = '';

        if (strlen(trim($this->targaRicerca)) < 3) {
            $this->addError('targaRicerca', 'Inserire almeno 3 caratteri.');
            return;
        }

        $veicolo = Veicolo::where('targa', 'like', '%' . trim($this->targaRicerca) . '%')->first();

        if (!$veicolo) {
            $this->addError('targaRicerca', 'Veicolo non trovato.');
            return;
        }

        $this->veicoloId = $veicolo->id;
        $this->clienteId = $veicolo->clientePrincipale?->id
            ?? $veicolo->clienti()->wherePivot('proprietario_attuale', true)->first()?->id;
        $this->resetValidation('targaRicerca');
    }

    public function selezionaAzione(string $azione): void
    {
        $this->azioneAttiva             = $azione;
        $this->pneumaticoSelezionatoId  = null;
        $this->ubicazione               = '';
        $this->usura_percentuale        = 0;
        $this->usura_note               = '';
        $this->km_deposito              = '';
        $this->note_deposito            = '';
        $this->km_monta                 = '';
        $this->marca_nuovo              = '';
        $this->misura_nuovo             = '';
        $this->note_nuovo               = '';
        $this->operazioneCompletata     = false;
        $this->messaggioEsito           = '';
    }

    public function eseguiDeposita(): void
    {
        $this->validate([
            'pneumaticoSelezionatoId' => ['required', 'integer'],
            'ubicazione'              => ['nullable', 'string', 'max:100'],
            'usura_percentuale'       => ['required', 'integer', 'min:0', 'max:100'],
            'usura_note'              => ['nullable', 'string', 'max:200'],
            'km_deposito'             => ['nullable', 'integer', 'min:0'],
        ]);

        $p = Pneumatico::findOrFail($this->pneumaticoSelezionatoId);
        $p->update(['stato' => StatoPneumatico::InDeposito]);

        DepositoPneumatico::create([
            'pneumatico_id'    => $p->id,
            'azione'           => AzioneDeposito::Deposito,
            'commessa_id'      => $this->commessaId,
            'data_azione'      => now()->toDateString(),
            'ubicazione'       => $this->ubicazione ?: null,
            'usura_percentuale' => $this->usura_percentuale,
            'usura_note'       => $this->usura_note ?: null,
            'km_al_momento'    => $this->km_deposito ?: null,
            'user_id'          => auth()->id(),
            'note'             => $this->note_deposito ?: null,
        ]);

        $this->operazioneCompletata = true;
        $this->messaggioEsito       = 'Set depositato correttamente. Ubicazione: ' . ($this->ubicazione ?: 'non specificata');
        $this->azioneAttiva = '';
    }

    public function eseguiMonta(): void
    {
        $this->validate([
            'pneumaticoSelezionatoId' => ['required', 'integer'],
            'km_monta'                => ['nullable', 'integer', 'min:0'],
        ]);

        $p = Pneumatico::findOrFail($this->pneumaticoSelezionatoId);
        $p->update(['stato' => StatoPneumatico::Montato]);

        DepositoPneumatico::create([
            'pneumatico_id' => $p->id,
            'azione'        => AzioneDeposito::Ritiro,
            'commessa_id'   => $this->commessaId,
            'data_azione'   => now()->toDateString(),
            'km_al_momento' => $this->km_monta ?: null,
            'user_id'       => auth()->id(),
        ]);

        $this->operazioneCompletata = true;
        $this->messaggioEsito       = 'Set montato sul veicolo.';
        $this->azioneAttiva = '';
    }

    public function eseguiNuovoSet(): void
    {
        $this->validate([
            'stagione_nuovo' => ['required', 'in:estivo,invernale,quattro_stagioni'],
            'marca_nuovo'    => ['required', 'string', 'max:100'],
            'misura_nuovo'   => ['required', 'string', 'max:30'],
        ]);

        $p = Pneumatico::create([
            'veicolo_id' => $this->veicoloId,
            'cliente_id' => $this->clienteId,
            'stagione'   => $this->stagione_nuovo,
            'marca'      => $this->marca_nuovo,
            'misura'     => $this->misura_nuovo,
            'stato'      => StatoPneumatico::Montato,
            'note'       => $this->note_nuovo ?: null,
        ]);

        DepositoPneumatico::create([
            'pneumatico_id' => $p->id,
            'azione'        => AzioneDeposito::Deposito,
            'commessa_id'   => $this->commessaId,
            'data_azione'   => now()->toDateString(),
            'user_id'       => auth()->id(),
            'note'          => 'Set nuovo registrato',
        ]);

        $this->operazioneCompletata = true;
        $this->messaggioEsito       = 'Nuovo set registrato e impostato come montato.';
        $this->azioneAttiva = '';
    }

    public function eseguiSmaltimento(): void
    {
        $this->validate([
            'pneumaticoSelezionatoId' => ['required', 'integer'],
        ]);

        $p = Pneumatico::findOrFail($this->pneumaticoSelezionatoId);
        $p->update(['stato' => StatoPneumatico::Smaltito]);

        DepositoPneumatico::create([
            'pneumatico_id' => $p->id,
            'azione'        => AzioneDeposito::Smaltimento,
            'commessa_id'   => $this->commessaId,
            'data_azione'   => now()->toDateString(),
            'user_id'       => auth()->id(),
        ]);

        $this->operazioneCompletata = true;
        $this->messaggioEsito       = 'Set marcato come smaltito.';
        $this->azioneAttiva = '';
    }

    public function render()
    {
        $veicolo         = $this->veicoloId ? Veicolo::with('clientePrincipale')->find($this->veicoloId) : null;
        $pneumatici      = collect();
        $montati         = collect();
        $inDeposito      = collect();

        if ($veicolo) {
            $pneumatici = Pneumatico::where('veicolo_id', $this->veicoloId)
                ->whereIn('stato', ['montato', 'in_deposito'])
                ->with('movimenti')
                ->get();

            $montati    = $pneumatici->where('stato', StatoPneumatico::Montato);
            $inDeposito = $pneumatici->where('stato', StatoPneumatico::InDeposito);
        }

        return view('livewire.pneumatici.accettazione-deposito', compact(
            'veicolo', 'pneumatici', 'montati', 'inDeposito'
        ));
    }
}
