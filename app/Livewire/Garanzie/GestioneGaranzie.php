<?php

namespace App\Livewire\Garanzie;

use App\Enums\TipoGaranzia;
use App\Models\CasaMadre;
use App\Models\Garanzia;
use App\Models\Veicolo;
use Livewire\Attributes\Rule;
use Livewire\Component;

class GestioneGaranzie extends Component
{
    public Veicolo $veicolo;
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|in:garanzia_costruttore,garanzia_usato,garanzia_riparazione,garanzia_ricambio,convenzione')]
    public string $tipo = 'garanzia_costruttore';

    #[Rule('required|string|max:255')]
    public string $descrizione = '';

    #[Rule('required|date')]
    public string $data_inizio = '';

    #[Rule('nullable|date|after_or_equal:data_inizio')]
    public ?string $data_fine = null;

    #[Rule('nullable|integer|min:0')]
    public ?int $km_inizio = null;

    #[Rule('nullable|integer|min:0')]
    public ?int $km_fine = null;

    #[Rule('nullable|string|max:100')]
    public ?string $numero_pratica = null;

    #[Rule('nullable|string')]
    public ?string $note = null;

    public bool $attiva = true;

    #[Rule('nullable|exists:case_madri,id')]
    public ?int $casa_madre_id = null;

    public function mount(int $veicoloId): void
    {
        $this->veicolo = Veicolo::findOrFail($veicoloId);
        $this->data_inizio = now()->toDateString();
    }

    public function apriModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->resetValidation();

        if ($id) {
            $g = Garanzia::findOrFail($id);
            $this->tipo           = $g->tipo->value;
            $this->descrizione    = $g->descrizione;
            $this->data_inizio    = $g->data_inizio->toDateString();
            $this->data_fine      = $g->data_fine?->toDateString();
            $this->km_inizio      = $g->km_inizio;
            $this->km_fine        = $g->km_fine;
            $this->numero_pratica = $g->numero_pratica;
            $this->note           = $g->note;
            $this->attiva         = $g->attiva;
            $this->casa_madre_id  = $g->casa_madre_id;
        } else {
            $this->reset(['descrizione', 'data_fine', 'km_inizio', 'km_fine', 'numero_pratica', 'note', 'casa_madre_id']);
            $this->tipo       = 'garanzia_costruttore';
            $this->attiva     = true;
            $this->data_inizio = now()->toDateString();
        }

        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'veicolo_id'     => $this->veicolo->id,
            'tipo'           => $this->tipo,
            'descrizione'    => $this->descrizione,
            'data_inizio'    => $this->data_inizio,
            'data_fine'      => $this->data_fine ?: null,
            'km_inizio'      => $this->km_inizio,
            'km_fine'        => $this->km_fine,
            'numero_pratica' => $this->numero_pratica,
            'note'           => $this->note,
            'attiva'         => $this->attiva,
            'casa_madre_id'  => $this->casa_madre_id,
        ];

        if ($this->editingId) {
            Garanzia::findOrFail($this->editingId)->update($dati);
            session()->flash('success', 'Garanzia aggiornata.');
        } else {
            Garanzia::create($dati);
            session()->flash('success', 'Garanzia creata.');
        }

        $this->showModal = false;
    }

    public function elimina(int $id): void
    {
        Garanzia::findOrFail($id)->delete();
    }

    public function render()
    {
        $garanzie   = $this->veicolo->garanzie()->with('casaMadre')->get();
        $caseMadri  = CasaMadre::orderBy('ragione_sociale')->get();
        $tipiGaranzia = TipoGaranzia::cases();

        return view('livewire.garanzie.gestione-garanzie', compact('garanzie', 'caseMadri', 'tipiGaranzia'));
    }
}
