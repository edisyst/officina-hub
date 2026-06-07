<?php

namespace App\Livewire\Magazzino;

use App\Models\CategoriaArticolo;
use Livewire\Attributes\Rule;
use Livewire\Component;

class GestioneCategorie extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|string|max:100')]
    public string $nome = '';

    #[Rule('nullable|string|max:255')]
    public string $descrizione = '';

    #[Rule('nullable|integer|exists:categorie_articoli,id')]
    public ?int $parent_id = null;

    public function apriModal(?int $id = null, ?int $parentId = null): void
    {
        $this->editingId = $id;
        $this->resetValidation();

        if ($id) {
            $cat = CategoriaArticolo::findOrFail($id);
            $this->fill([
                'nome'        => $cat->nome,
                'descrizione' => $cat->descrizione ?? '',
                'parent_id'   => $cat->parent_id,
            ]);
        } else {
            $this->reset(['nome', 'descrizione']);
            $this->parent_id = $parentId;
        }

        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'nome'        => $this->nome,
            'descrizione' => $this->descrizione ?: null,
            'parent_id'   => $this->parent_id,
        ];

        if ($this->editingId) {
            CategoriaArticolo::findOrFail($this->editingId)->update($dati);
        } else {
            $max = CategoriaArticolo::where('parent_id', $this->parent_id)->max('ordinamento') ?? 0;
            CategoriaArticolo::create(array_merge($dati, ['ordinamento' => $max + 1]));
        }

        $this->showModal = false;
    }

    public function elimina(int $id): void
    {
        $cat = CategoriaArticolo::withCount('articoli')->findOrFail($id);
        if ($cat->articoli_count > 0) {
            session()->flash('error', 'Impossibile eliminare: la categoria ha articoli associati.');
            return;
        }
        $cat->delete();
    }

    public function aggiornaOrdinamento(array $ordine): void
    {
        foreach ($ordine as $indice => $id) {
            CategoriaArticolo::where('id', $id)->update(['ordinamento' => $indice]);
        }
    }

    public function render()
    {
        $categorie = CategoriaArticolo::with(['figli.articoli'])
            ->radici()
            ->get();

        return view('livewire.magazzino.gestione-categorie', compact('categorie'));
    }
}
