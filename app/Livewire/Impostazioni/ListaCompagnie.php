<?php

namespace App\Livewire\Impostazioni;

use App\Models\CompagniaAssicurativa;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListaCompagnie extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|string|max:255')]
    public string $nome = '';

    #[Rule('nullable|string|max:20')]
    public string $codice_abi = '';

    #[Rule('nullable|email|max:255')]
    public string $email = '';

    #[Rule('nullable|email|max:255')]
    public string $pec = '';

    #[Rule('nullable|string|max:30')]
    public string $telefono = '';

    #[Rule('nullable|string|max:255')]
    public string $indirizzo = '';

    #[Rule('nullable|string|max:255')]
    public string $referente = '';

    #[Rule('nullable|string')]
    public string $note = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function apriCrea(): void
    {
        $this->authorize('create', CompagniaAssicurativa::class);
        $this->reset(['editingId', 'nome', 'codice_abi', 'email', 'pec', 'telefono', 'indirizzo', 'referente', 'note']);
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $compagnia = CompagniaAssicurativa::findOrFail($id);
        $this->authorize('update', $compagnia);

        $this->editingId  = $id;
        $this->nome       = $compagnia->nome;
        $this->codice_abi = $compagnia->codice_abi ?? '';
        $this->email      = $compagnia->email ?? '';
        $this->pec        = $compagnia->pec ?? '';
        $this->telefono   = $compagnia->telefono ?? '';
        $this->indirizzo  = $compagnia->indirizzo ?? '';
        $this->referente  = $compagnia->referente ?? '';
        $this->note       = $compagnia->note ?? '';
        $this->showModal  = true;
    }

    public function salva(): void
    {
        $this->validate();

        $data = [
            'nome'       => $this->nome,
            'codice_abi' => $this->codice_abi ?: null,
            'email'      => $this->email ?: null,
            'pec'        => $this->pec ?: null,
            'telefono'   => $this->telefono ?: null,
            'indirizzo'  => $this->indirizzo ?: null,
            'referente'  => $this->referente ?: null,
            'note'       => $this->note ?: null,
        ];

        if ($this->editingId) {
            $compagnia = CompagniaAssicurativa::findOrFail($this->editingId);
            $this->authorize('update', $compagnia);
            $compagnia->update($data);
        } else {
            $this->authorize('create', CompagniaAssicurativa::class);
            CompagniaAssicurativa::create($data);
        }

        $this->showModal = false;
        session()->flash('success', 'Compagnia salvata.');
    }

    public function elimina(int $id): void
    {
        $compagnia = CompagniaAssicurativa::findOrFail($id);
        $this->authorize('delete', $compagnia);
        $compagnia->delete();
        session()->flash('success', 'Compagnia eliminata.');
    }

    public function render()
    {
        $compagnie = CompagniaAssicurativa::withTrashed(false)
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('nome')
            ->paginate(15);

        return view('livewire.impostazioni.lista-compagnie', compact('compagnie'));
    }
}
