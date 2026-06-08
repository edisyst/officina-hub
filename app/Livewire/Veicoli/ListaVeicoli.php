<?php

namespace App\Livewire\Veicoli;

use App\Enums\Alimentazione;
use App\Enums\TipoVeicolo;
use App\Models\Veicolo;
use App\Services\LookupTarga\LookupTargaService;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListaVeicoli extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|in:auto,moto')]
    public string $tipo = 'auto';

    #[Rule('nullable|string|max:20')]
    public ?string $targa = null;

    #[Rule('nullable|string|max:17')]
    public ?string $vin = null;

    #[Rule('required|string|max:100')]
    public string $marca = '';

    #[Rule('required|string|max:100')]
    public string $modello = '';

    #[Rule('nullable|string|max:100')]
    public ?string $versione = null;

    #[Rule('required|in:benzina,diesel,ibrido,elettrico,gpl,metano')]
    public string $alimentazione = 'benzina';

    #[Rule('nullable|integer|min:0')]
    public ?int $cilindrata = null;

    #[Rule('nullable|integer|min:1900|max:2100')]
    public ?int $anno_immatricolazione = null;

    #[Rule('nullable|string|max:50')]
    public ?string $colore = null;

    #[Rule('nullable|integer|min:0')]
    public ?int $km_attuali = null;

    #[Rule('nullable|string')]
    public ?string $note = null;

    // Lookup targa
    public string  $lookupMessaggio    = '';
    public bool    $lookupCaricamento  = false;

    public function cercaTarga(): void
    {
        $service = app(LookupTargaService::class);

        if (! $service->isAbilitato()) {
            return;
        }

        $targa = strtoupper(trim($this->targa ?? ''));
        if (empty($targa)) {
            return;
        }

        $this->lookupCaricamento = true;
        $this->lookupMessaggio   = '';

        $dati = $service->cerca($targa);

        $this->lookupCaricamento = false;

        if ($dati === null) {
            $this->lookupMessaggio = 'Targa non trovata o servizio temporaneamente non disponibile. Inserire i dati manualmente.';
            return;
        }

        // Pre-compila i campi (rimangono modificabili)
        if (! empty($dati['marca']))                $this->marca                = $dati['marca'];
        if (! empty($dati['modello']))              $this->modello              = $dati['modello'];
        if (! empty($dati['versione']))             $this->versione             = $dati['versione'];
        if (! empty($dati['anno_immatricolazione'])) $this->anno_immatricolazione = $dati['anno_immatricolazione'];
        if (! empty($dati['alimentazione']))        $this->alimentazione        = $this->mappaAlimentazione($dati['alimentazione']);
        if (! empty($dati['cilindrata']))           $this->cilindrata           = $dati['cilindrata'];
        if (! empty($dati['colore']))               $this->colore               = $dati['colore'];
    }

    private function mappaAlimentazione(string $value): string
    {
        $map = [
            'benzina'   => 'benzina',
            'gasoline'  => 'benzina',
            'petrol'    => 'benzina',
            'diesel'    => 'diesel',
            'gasolio'   => 'diesel',
            'ibrido'    => 'ibrido',
            'hybrid'    => 'ibrido',
            'elettrico' => 'elettrico',
            'electric'  => 'elettrico',
            'gpl'       => 'gpl',
            'lpg'       => 'gpl',
            'metano'    => 'metano',
            'cng'       => 'metano',
        ];

        return $map[strtolower($value)] ?? 'benzina';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function apriModal(?int $id = null): void
    {
        $this->reset(['tipo', 'targa', 'vin', 'marca', 'modello', 'versione',
            'alimentazione', 'cilindrata', 'anno_immatricolazione', 'colore', 'km_attuali', 'note']);
        $this->tipo = 'auto';
        $this->alimentazione = 'benzina';
        $this->editingId = $id;

        if ($id) {
            $veicolo = Veicolo::findOrFail($id);
            $this->fill([
                'tipo' => $veicolo->tipo->value,
                'targa' => $veicolo->targa,
                'vin' => $veicolo->vin,
                'marca' => $veicolo->marca,
                'modello' => $veicolo->modello,
                'versione' => $veicolo->versione,
                'alimentazione' => $veicolo->alimentazione->value,
                'cilindrata' => $veicolo->cilindrata,
                'anno_immatricolazione' => $veicolo->anno_immatricolazione,
                'colore' => $veicolo->colore,
                'km_attuali' => $veicolo->km_attuali,
                'note' => $veicolo->note,
            ]);
        }

        $this->showModal = true;
    }

    public function chiudiModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'tipo' => $this->tipo,
            'targa' => $this->targa ? strtoupper($this->targa) : null,
            'vin' => $this->vin ? strtoupper($this->vin) : null,
            'marca' => $this->marca,
            'modello' => $this->modello,
            'versione' => $this->versione,
            'alimentazione' => $this->alimentazione,
            'cilindrata' => $this->cilindrata,
            'anno_immatricolazione' => $this->anno_immatricolazione,
            'colore' => $this->colore,
            'km_attuali' => $this->km_attuali,
            'note' => $this->note,
        ];

        if ($this->editingId) {
            $veicolo = Veicolo::findOrFail($this->editingId);
            $this->authorize('update', $veicolo);
            $veicolo->update($dati);
            session()->flash('success', 'Veicolo aggiornato con successo.');
        } else {
            $this->authorize('create', Veicolo::class);
            Veicolo::create($dati);
            session()->flash('success', 'Veicolo creato con successo.');
        }

        $this->chiudiModal();
    }

    public function elimina(int $id): void
    {
        $veicolo = Veicolo::findOrFail($id);
        $this->authorize('delete', $veicolo);
        $veicolo->delete();
        session()->flash('success', 'Veicolo eliminato.');
    }

    public function render()
    {
        $veicoli = Veicolo::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->withCount('commesse')
            ->orderBy('marca')
            ->paginate(20);

        return view('livewire.veicoli.lista-veicoli', [
            'veicoli' => $veicoli,
            'tipiVeicolo' => TipoVeicolo::cases(),
            'alimentazioni' => Alimentazione::cases(),
        ]);
    }
}
