<?php

namespace App\Livewire\Clienti;

use App\Enums\TipoCliente;
use App\Models\Cliente;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListaClienti extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $showTrashedModal = false;
    public ?int $editingId = null;

    #[Rule('required|in:fisica,giuridica')]
    public string $tipo = 'fisica';

    #[Rule('nullable|string|max:100')]
    public ?string $nome = null;

    #[Rule('nullable|string|max:100')]
    public ?string $cognome = null;

    #[Rule('nullable|string|max:200')]
    public ?string $ragione_sociale = null;

    #[Rule('nullable|string|max:16')]
    public ?string $codice_fiscale = null;

    #[Rule('nullable|string|max:11')]
    public ?string $partita_iva = null;

    #[Rule('nullable|email|max:255')]
    public ?string $email = null;

    #[Rule('nullable|string|max:30')]
    public ?string $telefono = null;

    #[Rule('nullable|string|max:255')]
    public ?string $indirizzo = null;

    #[Rule('nullable|string|max:100')]
    public ?string $citta = null;

    #[Rule('nullable|string|max:10')]
    public ?string $cap = null;

    #[Rule('nullable|string|max:5')]
    public ?string $provincia = null;

    #[Rule('nullable|string')]
    public ?string $note = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function apriModal(?int $id = null): void
    {
        $this->reset(['tipo', 'nome', 'cognome', 'ragione_sociale', 'codice_fiscale',
            'partita_iva', 'email', 'telefono', 'indirizzo', 'citta', 'cap', 'provincia', 'note']);
        $this->editingId = $id;

        if ($id) {
            $cliente = Cliente::findOrFail($id);
            $this->fill([
                'tipo' => $cliente->tipo->value,
                'nome' => $cliente->nome,
                'cognome' => $cliente->cognome,
                'ragione_sociale' => $cliente->ragione_sociale,
                'codice_fiscale' => $cliente->codice_fiscale,
                'partita_iva' => $cliente->partita_iva,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'indirizzo' => $cliente->indirizzo,
                'citta' => $cliente->citta,
                'cap' => $cliente->cap,
                'provincia' => $cliente->provincia,
                'note' => $cliente->note,
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
            'nome' => $this->nome,
            'cognome' => $this->cognome,
            'ragione_sociale' => $this->ragione_sociale,
            'codice_fiscale' => $this->codice_fiscale ?: null,
            'partita_iva' => $this->partita_iva ?: null,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'indirizzo' => $this->indirizzo,
            'citta' => $this->citta,
            'cap' => $this->cap,
            'provincia' => $this->provincia,
            'note' => $this->note,
        ];

        if ($this->editingId) {
            $cliente = Cliente::findOrFail($this->editingId);
            $this->authorize('update', $cliente);
            $cliente->update($dati);
            session()->flash('success', 'Cliente aggiornato con successo.');
        } else {
            $this->authorize('create', Cliente::class);
            Cliente::create($dati);
            session()->flash('success', 'Cliente creato con successo.');
        }

        $this->chiudiModal();
    }

    public function elimina(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->authorize('delete', $cliente);
        $cliente->delete();
        session()->flash('success', 'Cliente eliminato.');
    }

    public function ripristina(int $id): void
    {
        $cliente = Cliente::withTrashed()->findOrFail($id);
        $this->authorize('restore', $cliente);
        $cliente->restore();
        session()->flash('success', 'Cliente ripristinato.');
    }

    public function render()
    {
        $clienti = Cliente::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('cognome')
            ->orderBy('ragione_sociale')
            ->paginate(20);

        $eliminati = Cliente::onlyTrashed()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->get();

        return view('livewire.clienti.lista-clienti', [
            'clienti' => $clienti,
            'eliminati' => $eliminati,
            'tipiCliente' => TipoCliente::cases(),
        ]);
    }
}
