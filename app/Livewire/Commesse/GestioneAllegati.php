<?php

namespace App\Livewire\Commesse;

use App\Models\Allegato;
use App\Models\Commessa;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class GestioneAllegati extends Component
{
    use WithFileUploads;

    public Commessa $commessa;
    public array $files = [];
    public string $descrizione = '';

    public function mount(int $commessaId): void
    {
        $this->commessa = Commessa::findOrFail($commessaId);
    }

    public function upload(): void
    {
        $this->validate([
            'files.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,'
                    . 'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                    . 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'text/plain',
                'max:10240',
            ],
        ]);

        $this->authorize('update', $this->commessa);

        foreach ($this->files as $file) {
            $nomeFile = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8)
                . '.' . $file->getClientOriginalExtension();
            $percorso = $file->storeAs("allegati/{$this->commessa->id}", $nomeFile, 'local');

            Allegato::create([
                'commessa_id' => $this->commessa->id,
                'nome_file' => $file->getClientOriginalName(),
                'percorso' => $percorso,
                'mime_type' => $file->getMimeType(),
                'dimensione_bytes' => $file->getSize(),
                'descrizione' => $this->descrizione ?: null,
                'user_id' => auth()->id(),
            ]);
        }

        $this->reset(['files', 'descrizione']);
        $this->commessa->load('allegati');
        session()->flash('success', 'Allegati caricati con successo.');
    }

    public function elimina(int $id): void
    {
        $allegato = Allegato::findOrFail($id);
        $this->authorize('update', $this->commessa);
        \Illuminate\Support\Facades\Storage::delete($allegato->percorso);
        $allegato->delete();
        $this->commessa->load('allegati');
    }

    public function render()
    {
        $allegati = $this->commessa->allegati()->with('user')->latest()->get();

        return view('livewire.commesse.gestione-allegati', compact('allegati'));
    }
}
