<?php

namespace App\Livewire\Carrozzeria;

use App\Enums\TipoDanno;
use App\Enums\ZonaDanno;
use App\Models\DannoVeicolo;
use Livewire\Attributes\Rule;
use Livewire\Component;

class GestioneDanni extends Component
{
    public int $commessaId;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required')]
    public string $formZona = '';

    #[Rule('required')]
    public string $formTipoDanno = '';

    #[Rule('required|string|max:255')]
    public string $formDescrizione = '';

    #[Rule('required|numeric|min:0.01')]
    public string $formQuantita = '1';

    #[Rule('nullable|numeric|min:0')]
    public string $formPrezzoStimato = '';

    #[Rule('nullable|numeric|min:0')]
    public string $formPrezzoPerzia = '';

    public bool $formIncluso = true;

    #[Rule('nullable|string')]
    public string $formNote = '';

    public function mount(int $commessaId): void
    {
        $this->commessaId = $commessaId;
    }

    public function apriModalDanno(string $zona): void
    {
        $this->resetForm();
        $this->formZona  = $zona;
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $danno = DannoVeicolo::findOrFail($id);

        $this->editingId          = $id;
        $this->formZona           = $danno->zona->value;
        $this->formTipoDanno      = $danno->tipo_danno->value;
        $this->formDescrizione    = $danno->descrizione;
        $this->formQuantita       = (string) $danno->quantita;
        $this->formPrezzoStimato  = $danno->prezzo_stimato !== null ? (string) $danno->prezzo_stimato : '';
        $this->formPrezzoPerzia   = $danno->prezzo_perizia !== null ? (string) $danno->prezzo_perizia : '';
        $this->formIncluso        = (bool) $danno->incluso_in_perizia;
        $this->formNote           = $danno->note ?? '';
        $this->showModal          = true;
    }

    public function salvaDanno(): void
    {
        $this->validate();

        $data = [
            'commessa_id'        => $this->commessaId,
            'zona'               => $this->formZona,
            'tipo_danno'         => $this->formTipoDanno,
            'descrizione'        => $this->formDescrizione,
            'quantita'           => $this->formQuantita,
            'prezzo_stimato'     => $this->formPrezzoStimato !== '' ? $this->formPrezzoStimato : null,
            'prezzo_perizia'     => $this->formPrezzoPerzia !== '' ? $this->formPrezzoPerzia : null,
            'incluso_in_perizia' => $this->formIncluso,
            'note'               => $this->formNote ?: null,
        ];

        if ($this->editingId) {
            DannoVeicolo::findOrFail($this->editingId)->update($data);
        } else {
            DannoVeicolo::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function eliminaDanno(int $id): void
    {
        DannoVeicolo::findOrFail($id)->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'formZona', 'formTipoDanno', 'formDescrizione', 'formNote', 'formPrezzoStimato', 'formPrezzoPerzia']);
        $this->formQuantita = '1';
        $this->formIncluso  = true;
    }

    public function render()
    {
        $danni = DannoVeicolo::where('commessa_id', $this->commessaId)
            ->orderBy('ordinamento')
            ->orderBy('id')
            ->get();

        $danniPerZona = $danni->groupBy(fn($d) => $d->zona->value)->map->count()->toArray();

        $totaleStimato = $danni->sum(fn($d) => (float) $d->prezzo_stimato * (float) $d->quantita);
        $totalePerizia = $danni->where('incluso_in_perizia', true)->sum(fn($d) => (float) $d->prezzo_perizia * (float) $d->quantita);

        return view('livewire.carrozzeria.gestione-danni', [
            'danni'       => $danni,
            'danniPerZona'=> $danniPerZona,
            'zone'        => ZonaDanno::cases(),
            'tipiDanno'   => TipoDanno::cases(),
            'totaleStimato'=> $totaleStimato,
            'totalePerizia'=> $totalePerizia,
        ]);
    }
}
