<?php

namespace App\Livewire\Impostazioni;

use App\Models\TariffaOraria;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TariffeOrarie extends Component
{
    public bool $showModal  = false;
    public ?int $tariffaId  = null;

    public string $nome         = '';
    public string $tariffaOraria = '';
    public bool $is_attiva      = true;

    public function apriNuovo(): void
    {
        $this->reset(['tariffaId', 'nome', 'tariffaOraria']);
        $this->is_attiva = true;
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $t = TariffaOraria::findOrFail($id);
        $this->tariffaId    = $t->id;
        $this->nome         = $t->nome;
        $this->tariffaOraria = (string) $t->tariffa_oraria;
        $this->is_attiva    = $t->is_attiva;
        $this->showModal    = true;
    }

    public function salva(): void
    {
        $this->validate([
            'nome'         => 'required|string|max:255',
            'tariffaOraria' => 'required|numeric|min:0',
        ]);

        $dati = [
            'nome'           => $this->nome,
            'tariffa_oraria' => $this->tariffaOraria,
            'is_attiva'      => $this->is_attiva,
        ];

        if ($this->tariffaId) {
            TariffaOraria::findOrFail($this->tariffaId)->update($dati);
            session()->flash('success', 'Tariffa oraria aggiornata.');
        } else {
            TariffaOraria::create($dati);
            session()->flash('success', 'Tariffa oraria creata.');
        }

        $this->showModal = false;
    }

    public function impostaDefault(int $id): void
    {
        DB::transaction(function () use ($id) {
            TariffaOraria::where('id', '!=', $id)->update(['is_default' => false]);
            TariffaOraria::where('id', $id)->update(['is_default' => true]);
        });
        session()->flash('success', 'Tariffa oraria impostata come default.');
    }

    public function toggleAttiva(int $id): void
    {
        $t = TariffaOraria::findOrFail($id);
        if ($t->is_default && $t->is_attiva) {
            session()->flash('error', 'Non puoi disattivare la tariffa oraria default. Imposta prima un\'altra come default.');
            return;
        }
        $t->update(['is_attiva' => ! $t->is_attiva]);
    }

    public function render()
    {
        $tariffe = TariffaOraria::orderByDesc('is_default')->orderBy('nome')->get();

        return view('livewire.impostazioni.tariffe-orarie', compact('tariffe'));
    }
}
