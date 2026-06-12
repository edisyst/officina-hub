<?php

namespace App\Livewire\Impostazioni;

use App\Models\CasaMadre;
use Livewire\Attributes\Rule;
use Livewire\Component;

class GestioneCaseMadri extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $cerca   = '';

    #[Rule('required|string|max:255')]
    public string $ragione_sociale = '';

    #[Rule('nullable|string|max:20')]
    public ?string $partita_iva = null;

    #[Rule('nullable|string|max:7')]
    public ?string $codice_destinatario_sdi = null;

    #[Rule('nullable|email|max:255')]
    public ?string $pec = null;

    #[Rule('nullable|email|max:255')]
    public ?string $email = null;

    #[Rule('nullable|string|max:30')]
    public ?string $telefono = null;

    #[Rule('nullable|string|max:100')]
    public ?string $codice_convenzionamento = null;

    #[Rule('nullable|string')]
    public ?string $note = null;

    public function apriModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->resetValidation();

        if ($id) {
            $cm = CasaMadre::findOrFail($id);
            $this->ragione_sociale         = $cm->ragione_sociale;
            $this->partita_iva             = $cm->partita_iva;
            $this->codice_destinatario_sdi = $cm->codice_destinatario_sdi;
            $this->pec                     = $cm->pec;
            $this->email                   = $cm->email;
            $this->telefono                = $cm->telefono;
            $this->codice_convenzionamento = $cm->codice_convenzionamento;
            $this->note                    = $cm->note;
        } else {
            $this->reset([
                'ragione_sociale', 'partita_iva', 'codice_destinatario_sdi',
                'pec', 'email', 'telefono', 'codice_convenzionamento', 'note',
            ]);
        }

        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'ragione_sociale'         => $this->ragione_sociale,
            'partita_iva'             => $this->partita_iva,
            'codice_destinatario_sdi' => $this->codice_destinatario_sdi,
            'pec'                     => $this->pec,
            'email'                   => $this->email,
            'telefono'                => $this->telefono,
            'codice_convenzionamento' => $this->codice_convenzionamento,
            'note'                    => $this->note,
        ];

        if ($this->editingId) {
            CasaMadre::findOrFail($this->editingId)->update($dati);
            session()->flash('success', 'Casa madre aggiornata.');
        } else {
            CasaMadre::create($dati);
            session()->flash('success', 'Casa madre creata.');
        }

        $this->showModal = false;
    }

    public function elimina(int $id): void
    {
        CasaMadre::findOrFail($id)->delete();
        session()->flash('success', 'Casa madre eliminata.');
    }

    public function render()
    {
        $caseMadri = CasaMadre::when(
            $this->cerca,
            fn($q) => $q->where('ragione_sociale', 'like', "%{$this->cerca}%")
                        ->orWhere('partita_iva', 'like', "%{$this->cerca}%")
        )->orderBy('ragione_sociale')->get();

        return view('livewire.impostazioni.gestione-case-madri', compact('caseMadri'));
    }
}
