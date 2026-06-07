<?php

namespace App\Livewire\Impostazioni;

use App\Models\ChecklistTemplate;
use App\Models\ChecklistVoce;
use Livewire\Component;

class GestioneChecklist extends Component
{
    // Template list
    public ?int $templateSelezionatoId = null;

    // Modal template
    public bool $showTemplateModal = false;
    public ?int $templateEditId     = null;
    public string $templateNome     = '';
    public string $templateDesc     = '';
    public bool   $templateAttivo   = true;

    // Modal voce
    public bool $showVoceModal    = false;
    public ?int $voceEditId       = null;
    public string $voceEtichetta  = '';
    public string $voceTipo       = 'si_no';
    public bool   $voceObblig     = false;
    public string $voceUnita      = '';

    public function aprirTemplateModal(?int $id = null): void
    {
        $this->templateEditId = $id;
        if ($id) {
            $t = ChecklistTemplate::findOrFail($id);
            $this->templateNome   = $t->nome;
            $this->templateDesc   = $t->descrizione ?? '';
            $this->templateAttivo = $t->attivo;
        } else {
            $this->templateNome   = '';
            $this->templateDesc   = '';
            $this->templateAttivo = true;
        }
        $this->showTemplateModal = true;
    }

    public function salvaTemplate(): void
    {
        $this->validate([
            'templateNome' => ['required', 'string', 'max:150'],
            'templateDesc' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->templateEditId) {
            ChecklistTemplate::findOrFail($this->templateEditId)->update([
                'nome'        => $this->templateNome,
                'descrizione' => $this->templateDesc ?: null,
                'attivo'      => $this->templateAttivo,
            ]);
        } else {
            $max = ChecklistTemplate::max('ordinamento') ?? 0;
            ChecklistTemplate::create([
                'nome'        => $this->templateNome,
                'descrizione' => $this->templateDesc ?: null,
                'attivo'      => $this->templateAttivo,
                'ordinamento' => $max + 1,
            ]);
        }

        $this->showTemplateModal = false;
    }

    public function toggleAttivo(int $id): void
    {
        $t = ChecklistTemplate::findOrFail($id);
        $t->update(['attivo' => ! $t->attivo]);
    }

    public function eliminaTemplate(int $id): void
    {
        ChecklistTemplate::findOrFail($id)->delete();
        if ($this->templateSelezionatoId === $id) {
            $this->templateSelezionatoId = null;
        }
    }

    public function selezionaTemplate(int $id): void
    {
        $this->templateSelezionatoId = $id;
    }

    public function riordinaVoci(array $ordini): void
    {
        foreach ($ordini as $item) {
            ChecklistVoce::where('id', $item['value'])->update(['ordinamento' => $item['order']]);
        }
    }

    public function apriVoceModal(?int $id = null): void
    {
        $this->voceEditId    = $id;
        if ($id) {
            $v = ChecklistVoce::findOrFail($id);
            $this->voceEtichetta = $v->etichetta;
            $this->voceTipo      = $v->tipo;
            $this->voceObblig    = $v->obbligatoria;
            $this->voceUnita     = $v->unita_misura ?? '';
        } else {
            $this->voceEtichetta = '';
            $this->voceTipo      = 'si_no';
            $this->voceObblig    = false;
            $this->voceUnita     = '';
        }
        $this->showVoceModal = true;
    }

    public function salvaVoce(): void
    {
        $this->validate([
            'voceEtichetta' => ['required', 'string', 'max:200'],
            'voceTipo'      => ['required', 'in:si_no,numerico,testo_libero,foto_obbligatoria'],
            'voceUnita'     => ['nullable', 'string', 'max:20'],
        ]);

        $data = [
            'etichetta'   => $this->voceEtichetta,
            'tipo'        => $this->voceTipo,
            'obbligatoria'=> $this->voceObblig,
            'unita_misura'=> $this->voceTipo === 'numerico' ? ($this->voceUnita ?: null) : null,
        ];

        if ($this->voceEditId) {
            ChecklistVoce::findOrFail($this->voceEditId)->update($data);
        } else {
            $max = ChecklistVoce::where('checklist_template_id', $this->templateSelezionatoId)->max('ordinamento') ?? 0;
            ChecklistVoce::create(array_merge($data, [
                'checklist_template_id' => $this->templateSelezionatoId,
                'ordinamento'           => $max + 1,
            ]));
        }

        $this->showVoceModal = false;
    }

    public function eliminaVoce(int $id): void
    {
        ChecklistVoce::findOrFail($id)->delete();
    }

    public function render()
    {
        $templates = ChecklistTemplate::orderBy('ordinamento')->get();

        $voci = $this->templateSelezionatoId
            ? ChecklistVoce::where('checklist_template_id', $this->templateSelezionatoId)->orderBy('ordinamento')->get()
            : collect();

        return view('livewire.impostazioni.gestione-checklist', compact('templates', 'voci'));
    }
}
