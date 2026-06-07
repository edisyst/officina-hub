<?php

namespace App\Livewire\Carrozzeria;

use App\Enums\FaseFoto;
use App\Enums\ZonaDanno;
use App\Models\FotoDanno;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class FotoDanni extends Component
{
    use WithFileUploads;

    public int $commessaId;

    /** @var array<mixed> Upload multiplo */
    public array $nuoveFoto = [];

    public string $uploadFase        = 'ingresso';
    public string $uploadDescrizione = '';

    public string $filtroFase = '';

    public function mount(int $commessaId): void
    {
        $this->commessaId = $commessaId;
    }

    public function updatingFiltroFase(): void
    {
        // trigger re-render automatico
    }

    public function uploadFoto(): void
    {
        $this->validate([
            'nuoveFoto'   => 'required|array|min:1',
            'nuoveFoto.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:10240',
            ],
            'uploadFase'  => 'required|in:ingresso,lavorazione,completamento',
        ]);

        foreach ($this->nuoveFoto as $file) {
            $nomefile = Str::random(16) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('allegati/foto-danni', $nomefile, 'local');

            FotoDanno::create([
                'commessa_id'      => $this->commessaId,
                'percorso'         => 'allegati/foto-danni/' . $nomefile,
                'nome_file'        => $file->getClientOriginalName(),
                'mime_type'        => $file->getMimeType(),
                'dimensione_bytes' => $file->getSize(),
                'fase'             => $this->uploadFase,
                'descrizione'      => $this->uploadDescrizione ?: null,
                'user_id'          => auth()->id(),
            ]);
        }

        $this->reset(['nuoveFoto', 'uploadDescrizione']);
        session()->flash('success', 'Foto caricate correttamente.');
    }

    public function eliminaFoto(int $id): void
    {
        $foto = FotoDanno::findOrFail($id);
        Storage::disk('local')->delete($foto->percorso);
        $foto->delete();
    }

    public function render()
    {
        $query = FotoDanno::where('commessa_id', $this->commessaId);
        if ($this->filtroFase) {
            $query->where('fase', $this->filtroFase);
        }
        $foto = $query->orderBy('fase')->orderBy('created_at')->get();

        return view('livewire.carrozzeria.foto-danni', [
            'foto'      => $foto,
            'fasiFoto'  => FaseFoto::cases(),
        ]);
    }
}
