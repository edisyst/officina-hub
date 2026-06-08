<?php

namespace App\Livewire\Pneumatici;

use App\Enums\StagionePneumatico;
use App\Enums\StatoPneumatico;
use App\Models\Pneumatico;
use App\Models\Veicolo;
use Livewire\Component;

class GestionePneumatici extends Component
{
    public int $veicoloId;
    public ?int $clienteId = null;

    // Modal aggiungi/modifica set
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $stagione      = 'invernale';
    public string $marca         = '';
    public string $modello       = '';
    public string $misura        = '';
    public string $larghezza     = '';
    public string $rapporto      = '';
    public string $diametro      = '';
    public string $indice_carico = '';
    public string $indice_velocita = '';
    public int    $numero_pezzi  = 4;
    public bool   $dotati_di_cerchi = false;
    public string $tipo_cerchi   = '';
    public string $anno_produzione = '';
    public string $stato         = 'in_deposito';
    public string $note          = '';

    // Modal conferma smaltimento
    public bool   $showConfermaSmaltimento = false;
    public ?int   $smaltimentoId           = null;

    protected function rules(): array
    {
        return [
            'stagione'          => ['required', 'in:estivo,invernale,quattro_stagioni'],
            'marca'             => ['required', 'string', 'max:100'],
            'modello'           => ['nullable', 'string', 'max:100'],
            'misura'            => ['required', 'string', 'max:30'],
            'larghezza'         => ['nullable', 'integer', 'min:100', 'max:400'],
            'rapporto'          => ['nullable', 'integer', 'min:20', 'max:100'],
            'diametro'          => ['nullable', 'integer', 'min:10', 'max:24'],
            'indice_carico'     => ['nullable', 'string', 'max:10'],
            'indice_velocita'   => ['nullable', 'string', 'max:5'],
            'numero_pezzi'      => ['required', 'integer', 'min:1', 'max:8'],
            'dotati_di_cerchi'  => ['boolean'],
            'tipo_cerchi'       => ['nullable', 'string', 'max:30'],
            'anno_produzione'   => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'stato'             => ['required', 'in:montato,in_deposito,smaltito,ritirato_cliente'],
            'note'              => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(int $veicoloId): void
    {
        $this->veicoloId = $veicoloId;
        $veicolo = Veicolo::findOrFail($veicoloId);
        $this->clienteId = $veicolo->clientePrincipale?->id
            ?? $veicolo->clienti()->wherePivot('proprietario_attuale', true)->first()?->id;
    }

    public function apriModal(?int $id = null): void
    {
        $this->resetForm();
        $this->editingId = $id;

        if ($id) {
            $p = Pneumatico::findOrFail($id);
            $this->stagione         = $p->stagione->value;
            $this->marca            = $p->marca;
            $this->modello          = $p->modello ?? '';
            $this->misura           = $p->misura;
            $this->larghezza        = (string)($p->larghezza ?? '');
            $this->rapporto         = (string)($p->rapporto ?? '');
            $this->diametro         = (string)($p->diametro ?? '');
            $this->indice_carico    = $p->indice_carico ?? '';
            $this->indice_velocita  = $p->indice_velocita ?? '';
            $this->numero_pezzi     = $p->numero_pezzi;
            $this->dotati_di_cerchi = $p->dotati_di_cerchi;
            $this->tipo_cerchi      = $p->tipo_cerchi ?? '';
            $this->anno_produzione  = (string)($p->anno_produzione ?? '');
            $this->stato            = $p->stato->value;
            $this->note             = $p->note ?? '';
        }

        $this->showModal = true;
    }

    public function salva(): void
    {
        $data = $this->validate();

        $payload = [
            'veicolo_id'        => $this->veicoloId,
            'cliente_id'        => $this->clienteId,
            'stagione'          => $data['stagione'],
            'marca'             => $data['marca'],
            'modello'           => $data['modello'] ?: null,
            'misura'            => $data['misura'],
            'larghezza'         => $data['larghezza'] ?: null,
            'rapporto'          => $data['rapporto'] ?: null,
            'diametro'          => $data['diametro'] ?: null,
            'indice_carico'     => $data['indice_carico'] ?: null,
            'indice_velocita'   => $data['indice_velocita'] ?: null,
            'numero_pezzi'      => $data['numero_pezzi'],
            'dotati_di_cerchi'  => $data['dotati_di_cerchi'],
            'tipo_cerchi'       => $data['tipo_cerchi'] ?: null,
            'anno_produzione'   => $data['anno_produzione'] ?: null,
            'stato'             => $data['stato'],
            'note'              => $data['note'] ?: null,
        ];

        if ($this->editingId) {
            Pneumatico::findOrFail($this->editingId)->update($payload);
        } else {
            Pneumatico::create($payload);
        }

        $this->showModal  = false;
        $this->editingId  = null;
        $this->resetForm();
    }

    public function avviaSmaltimento(int $id): void
    {
        $this->smaltimentoId         = $id;
        $this->showConfermaSmaltimento = true;
    }

    public function confermaSmaltimento(): void
    {
        if (!$this->smaltimentoId) return;

        $p = Pneumatico::findOrFail($this->smaltimentoId);
        $p->update(['stato' => StatoPneumatico::Smaltito]);

        $p->movimenti()->create([
            'azione'     => 'smaltimento',
            'data_azione' => now()->toDateString(),
            'user_id'    => auth()->id(),
        ]);

        $this->showConfermaSmaltimento = false;
        $this->smaltimentoId           = null;
    }

    public function chiudiModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId         = null;
        $this->stagione          = 'invernale';
        $this->marca             = '';
        $this->modello           = '';
        $this->misura            = '';
        $this->larghezza         = '';
        $this->rapporto          = '';
        $this->diametro          = '';
        $this->indice_carico     = '';
        $this->indice_velocita   = '';
        $this->numero_pezzi      = 4;
        $this->dotati_di_cerchi  = false;
        $this->tipo_cerchi       = '';
        $this->anno_produzione   = '';
        $this->stato             = 'in_deposito';
        $this->note              = '';
        $this->resetValidation();
    }

    public function render()
    {
        $pneumatici = Pneumatico::where('veicolo_id', $this->veicoloId)
            ->with('movimenti')
            ->orderByRaw("FIELD(stato, 'montato', 'in_deposito', 'smaltito', 'ritirato_cliente')")
            ->get();

        return view('livewire.pneumatici.gestione-pneumatici', [
            'pneumatici'     => $pneumatici,
            'stagioni'       => StagionePneumatico::cases(),
            'statiPneumatico' => StatoPneumatico::cases(),
        ]);
    }
}
