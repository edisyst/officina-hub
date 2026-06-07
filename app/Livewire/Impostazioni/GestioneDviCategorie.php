<?php

namespace App\Livewire\Impostazioni;

use App\Models\DviCategoria;
use Livewire\Component;

class GestioneDviCategorie extends Component
{
    public string $nome        = '';
    public string $iconaCss    = '';
    public bool $showForm      = false;
    public ?int $editingId     = null;

    public function apriForm(?int $id = null): void
    {
        $this->editingId = $id;
        if ($id) {
            $cat = DviCategoria::findOrFail($id);
            $this->nome     = $cat->nome;
            $this->iconaCss = $cat->icona_css ?? '';
        } else {
            $this->nome     = '';
            $this->iconaCss = '';
        }
        $this->showForm = true;
    }

    public function salva(): void
    {
        $this->validate([
            'nome'     => 'required|string|max:100',
            'iconaCss' => 'nullable|string|max:100',
        ]);

        if ($this->editingId) {
            DviCategoria::find($this->editingId)?->update([
                'nome'      => $this->nome,
                'icona_css' => $this->iconaCss ?: null,
            ]);
        } else {
            $max = DviCategoria::max('ordinamento') ?? 0;
            DviCategoria::create([
                'nome'        => $this->nome,
                'icona_css'   => $this->iconaCss ?: null,
                'ordinamento' => $max + 1,
                'attivo'      => true,
            ]);
        }

        $this->showForm  = false;
        $this->editingId = null;
        session()->flash('success', 'Categoria salvata.');
    }

    public function toggleAttivo(int $id): void
    {
        $cat = DviCategoria::findOrFail($id);
        $cat->update(['attivo' => ! $cat->attivo]);
    }

    public function elimina(int $id): void
    {
        DviCategoria::findOrFail($id)->delete();
    }

    public function riordina(array $ordine): void
    {
        foreach ($ordine as $i => $id) {
            DviCategoria::where('id', $id)->update(['ordinamento' => $i]);
        }
    }

    public function render()
    {
        return view('livewire.impostazioni.gestione-dvi-categorie', [
            'categorie' => DviCategoria::orderBy('ordinamento')->get(),
        ])->layout('layouts.app', ['title' => 'Categorie DVI']);
    }
}
