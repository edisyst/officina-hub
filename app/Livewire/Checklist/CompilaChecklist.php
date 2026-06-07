<?php

namespace App\Livewire\Checklist;

use App\Models\ChecklistCompilata;
use App\Models\ChecklistRisposta;
use App\Models\ChecklistTemplate;
use App\Models\Commessa;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompilaChecklist extends Component
{
    use WithFileUploads;

    public Commessa $commessa;
    public ChecklistTemplate $template;
    public ChecklistCompilata $compilata;

    // Stato risposte indicizzato per voce_id
    public array $risposte = [];

    // Upload foto temporanei
    public array $fotoTemp = [];

    public function mount(Commessa $commessa, ChecklistTemplate $template): void
    {
        $this->commessa = $commessa;
        $this->template = $template->load('voci');

        $this->compilata = ChecklistCompilata::firstOrCreate(
            [
                'checklist_template_id' => $template->id,
                'commessa_id'           => $commessa->id,
            ],
            ['user_id' => auth()->id()]
        );

        // Precarica risposte esistenti
        $this->compilata->load('risposte');
        foreach ($this->compilata->risposte as $r) {
            $this->risposte[$r->checklist_voce_id] = match ($r->voce->tipo ?? '') {
                'si_no'  => $r->valore_booleano,
                'numerico' => $r->valore_numerico,
                default  => $r->valore_testo,
            };
        }
    }

    public function salvaRisposta(int $voceId): void
    {
        if ($this->compilata->isCompletata()) return;

        $voce = $this->template->voci->firstWhere('id', $voceId);
        if (! $voce) return;

        $valore = $this->risposte[$voceId] ?? null;

        $data = [
            'valore_booleano'  => null,
            'valore_numerico'  => null,
            'valore_testo'     => null,
        ];

        match ($voce->tipo) {
            'si_no'      => $data['valore_booleano'] = $valore !== null ? (bool) $valore : null,
            'numerico'   => $data['valore_numerico'] = $valore !== '' && $valore !== null ? (float) $valore : null,
            default      => $data['valore_testo']    = $valore,
        };

        ChecklistRisposta::updateOrCreate(
            [
                'checklist_compilata_id' => $this->compilata->id,
                'checklist_voce_id'      => $voceId,
            ],
            $data
        );
    }

    public function salvaFoto(int $voceId): void
    {
        if ($this->compilata->isCompletata()) return;

        $foto = $this->fotoTemp[$voceId] ?? null;
        if (! $foto) return;

        $this->validate(["fotoTemp.{$voceId}" => ['image', 'max:4096']]);

        $path = $foto->store('allegati/checklist', 'local');

        ChecklistRisposta::updateOrCreate(
            [
                'checklist_compilata_id' => $this->compilata->id,
                'checklist_voce_id'      => $voceId,
            ],
            ['foto_path' => $path]
        );

        unset($this->fotoTemp[$voceId]);
    }

    public function completa(): void
    {
        if ($this->compilata->isCompletata()) return;

        // Verifica che le voci obbligatorie abbiano risposta
        $obbligatorie = $this->template->voci->where('obbligatoria', true)->pluck('id');
        $risposte     = $this->compilata->risposte()->pluck('checklist_voce_id');
        $mancanti     = $obbligatorie->diff($risposte);

        if ($mancanti->isNotEmpty()) {
            $this->addError('completamento', 'Completa tutte le voci obbligatorie prima di finalizzare.');
            return;
        }

        $this->compilata->update(['completata_at' => now()]);
        $this->compilata->refresh();

        session()->flash('success', 'Checklist completata!');
    }

    public function render()
    {
        $this->compilata->load('risposte.voce');

        $rispostePerId = $this->compilata->risposte->keyBy('checklist_voce_id');

        return view('livewire.checklist.compila-checklist', [
            'voci'         => $this->template->voci,
            'rispostePerId' => $rispostePerId,
        ]);
    }
}
