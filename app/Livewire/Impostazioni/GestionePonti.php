<?php

namespace App\Livewire\Impostazioni;

use App\Enums\TipoPonte;
use App\Models\Ponte;
use Livewire\Component;

class GestionePonti extends Component
{
    public bool $showModal  = false;
    public ?int $ponteId    = null;
    public string $nome     = '';
    public string $tipo     = 'meccanica';
    public string $descrizione = '';

    protected function rules(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:100'],
            'tipo'        => ['required', 'in:meccanica,carrozzeria,diagnosi'],
            'descrizione' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function apriNuovo(): void
    {
        $this->reset(['ponteId', 'nome', 'tipo', 'descrizione']);
        $this->tipo     = 'meccanica';
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $ponte = Ponte::findOrFail($id);
        $this->ponteId     = $ponte->id;
        $this->nome        = $ponte->nome;
        $this->tipo        = $ponte->tipo->value;
        $this->descrizione = $ponte->descrizione ?? '';
        $this->showModal   = true;
    }

    public function salva(): void
    {
        $this->validate();

        $data = [
            'nome'        => $this->nome,
            'tipo'        => $this->tipo,
            'descrizione' => $this->descrizione ?: null,
        ];

        if ($this->ponteId) {
            Ponte::findOrFail($this->ponteId)->update($data);
            session()->flash('success', 'Ponte aggiornato.');
        } else {
            $maxOrdinamento = Ponte::max('ordinamento') ?? 0;
            Ponte::create(array_merge($data, ['ordinamento' => $maxOrdinamento + 1]));
            session()->flash('success', 'Ponte creato.');
        }

        $this->showModal = false;
        $this->reset(['ponteId', 'nome', 'tipo', 'descrizione']);
    }

    public function toggleAttivo(int $id): void
    {
        $ponte = Ponte::findOrFail($id);
        $ponte->update(['attivo' => ! $ponte->attivo]);
    }

    public function aggiornaSortable(array $order): void
    {
        foreach ($order as $index => $id) {
            Ponte::where('id', $id)->update(['ordinamento' => $index + 1]);
        }
    }

    public function render()
    {
        return view('livewire.impostazioni.gestione-ponti', [
            'ponti'     => Ponte::orderBy('ordinamento')->get(),
            'tipiPonte' => TipoPonte::cases(),
        ]);
    }
}
