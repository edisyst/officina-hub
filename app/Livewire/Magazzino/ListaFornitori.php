<?php

namespace App\Livewire\Magazzino;

use App\Models\Fornitore;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListaFornitori extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|string|max:255')]
    public string $ragione_sociale = '';

    #[Rule('nullable|string|max:20')]
    public string $partita_iva = '';

    #[Rule('nullable|string|max:20')]
    public string $codice_fiscale = '';

    #[Rule('nullable|email|max:255')]
    public string $email = '';

    #[Rule('nullable|string|max:50')]
    public string $telefono = '';

    #[Rule('nullable|string|max:255')]
    public string $indirizzo = '';

    #[Rule('nullable|string|max:100')]
    public string $citta = '';

    #[Rule('nullable|string|max:10')]
    public string $cap = '';

    #[Rule('nullable|string|max:5')]
    public string $provincia = '';

    #[Rule('nullable|string')]
    public string $note = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function apriModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->resetValidation();

        if ($id) {
            $f = Fornitore::findOrFail($id);
            $this->fill([
                'ragione_sociale' => $f->ragione_sociale,
                'partita_iva'     => $f->partita_iva ?? '',
                'codice_fiscale'  => $f->codice_fiscale ?? '',
                'email'           => $f->email ?? '',
                'telefono'        => $f->telefono ?? '',
                'indirizzo'       => $f->indirizzo ?? '',
                'citta'           => $f->citta ?? '',
                'cap'             => $f->cap ?? '',
                'provincia'       => $f->provincia ?? '',
                'note'            => $f->note ?? '',
            ]);
        } else {
            $this->reset(['ragione_sociale', 'partita_iva', 'codice_fiscale', 'email', 'telefono', 'indirizzo', 'citta', 'cap', 'provincia', 'note']);
        }

        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();
        $this->authorize('create', Fornitore::class);

        $dati = [
            'ragione_sociale' => $this->ragione_sociale,
            'partita_iva'     => $this->partita_iva ?: null,
            'codice_fiscale'  => $this->codice_fiscale ?: null,
            'email'           => $this->email ?: null,
            'telefono'        => $this->telefono ?: null,
            'indirizzo'       => $this->indirizzo ?: null,
            'citta'           => $this->citta ?: null,
            'cap'             => $this->cap ?: null,
            'provincia'       => $this->provincia ?: null,
            'note'            => $this->note ?: null,
        ];

        if ($this->editingId) {
            Fornitore::findOrFail($this->editingId)->update($dati);
            session()->flash('success', 'Fornitore aggiornato.');
        } else {
            Fornitore::create($dati);
            session()->flash('success', 'Fornitore creato.');
        }

        $this->showModal = false;
    }

    public function elimina(int $id): void
    {
        $this->authorize('delete', Fornitore::findOrFail($id));
        Fornitore::findOrFail($id)->delete();
        session()->flash('success', 'Fornitore eliminato.');
    }

    public function render()
    {
        $fornitori = Fornitore::when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('ragione_sociale')
            ->paginate(20);

        return view('livewire.magazzino.lista-fornitori', compact('fornitori'));
    }
}
