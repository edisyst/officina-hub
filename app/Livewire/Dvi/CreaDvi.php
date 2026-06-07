<?php

namespace App\Livewire\Dvi;

use App\Enums\StatoDviIspezione;
use App\Enums\TipoDviMedia;
use App\Enums\UrgenzaDvi;
use App\Jobs\InviaDviCliente;
use App\Models\Commessa;
use App\Models\DviCategoria;
use App\Models\DviIspezione;
use App\Models\DviMedia;
use App\Models\DviVoce;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreaDvi extends Component
{
    use WithFileUploads;

    public Commessa $commessa;
    public ?DviIspezione $ispezione = null;

    public string $noteMeccanico = '';

    // Form voce
    public bool $showFormVoce = false;
    public ?int $editingVoceId = null;
    public string $vCategoria = '';
    public string $vDescrizone = '';
    public string $vUrgenza = 'ok';
    public string $vPrezzoStimato = '';
    public string $vNote = '';

    // Upload foto
    public $fotoUpload;
    public ?int $fotoVoceId = null;

    public function mount(Commessa $commessa): void
    {
        $this->commessa = $commessa->load(['cliente', 'veicolo']);

        $this->ispezione = DviIspezione::where('commessa_id', $commessa->id)
            ->where('stato', StatoDviIspezione::Bozza)
            ->with(['voci.media'])
            ->latest()
            ->first();
    }

    public function creaIspezione(): void
    {
        if ($this->ispezione) return;

        $this->ispezione = DviIspezione::create([
            'commessa_id'    => $this->commessa->id,
            'user_id'        => auth()->id(),
            'stato'          => StatoDviIspezione::Bozza,
            'note_meccanico' => '',
        ]);

        $this->ispezione->load('voci.media');
    }

    public function apriFormVoce(?int $voceId = null): void
    {
        $this->creaIspezione();
        $this->editingVoceId = $voceId;

        if ($voceId) {
            $voce = DviVoce::find($voceId);
            if ($voce) {
                $this->vCategoria    = $voce->categoria;
                $this->vDescrizone   = $voce->descrizione;
                $this->vUrgenza      = $voce->urgenza->value;
                $this->vPrezzoStimato = $voce->prezzo_stimato ?? '';
                $this->vNote         = $voce->note ?? '';
            }
        } else {
            $this->resetFormVoce();
        }

        $this->showFormVoce = true;
    }

    public function salvaVoce(): void
    {
        $this->validate([
            'vCategoria'  => 'required|string|max:100',
            'vDescrizone' => 'required|string|max:255',
            'vUrgenza'    => 'required|in:ok,attenzione,urgente',
            'vPrezzoStimato' => 'nullable|numeric|min:0',
        ]);

        $dati = [
            'dvi_ispezione_id' => $this->ispezione->id,
            'categoria'        => $this->vCategoria,
            'descrizione'      => $this->vDescrizone,
            'urgenza'          => $this->vUrgenza,
            'prezzo_stimato'   => $this->vPrezzoStimato ?: null,
            'note'             => $this->vNote ?: null,
        ];

        if ($this->editingVoceId) {
            DviVoce::find($this->editingVoceId)?->update($dati);
        } else {
            $max = DviVoce::where('dvi_ispezione_id', $this->ispezione->id)->max('ordinamento') ?? 0;
            $dati['ordinamento'] = $max + 1;
            DviVoce::create($dati);
        }

        $this->ispezione->refresh();
        $this->ispezione->load('voci.media');
        $this->chiudiFormVoce();
        session()->flash('success', 'Voce salvata.');
    }

    public function eliminaVoce(int $voceId): void
    {
        $voce = DviVoce::where('id', $voceId)
            ->where('dvi_ispezione_id', $this->ispezione->id)
            ->first();

        if (! $voce) return;

        // Elimina i file media
        foreach ($voce->media as $m) {
            Storage::disk('local')->delete($m->percorso);
            if ($m->thumbnail_path) {
                Storage::disk('local')->delete($m->thumbnail_path);
            }
        }

        $voce->delete();
        $this->ispezione->refresh();
        $this->ispezione->load('voci.media');
    }

    public function aggiornaNota(): void
    {
        if (! $this->ispezione) return;
        $this->ispezione->update(['note_meccanico' => $this->noteMeccanico]);
    }

    public function uploadFoto(int $voceId): void
    {
        $this->validate([
            'fotoUpload' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $voce = DviVoce::where('id', $voceId)
            ->where('dvi_ispezione_id', $this->ispezione->id)
            ->firstOrFail();

        $fotoCount = $voce->foto()->count();
        if ($fotoCount >= 10) {
            $this->addError('fotoUpload', 'Massimo 10 foto per voce.');
            return;
        }

        $anno  = now()->format('Y');
        $mese  = now()->format('m');
        $dir   = "dvi/foto/{$anno}/{$mese}";
        $ext   = $this->fotoUpload->getClientOriginalExtension() ?: 'jpg';
        $nome  = Str::uuid() . '.' . $ext;
        $path  = $this->fotoUpload->storeAs($dir, $nome, 'local');

        // Genera thumbnail
        $thumbNome = pathinfo($nome, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbPath = $dir . '/' . $thumbNome;
        try {
            $manager = new ImageManager(new Driver());
            $img = $manager->read(Storage::disk('local')->path($path));
            $img->scaleDown(400, 300);
            $img->save(Storage::disk('local')->path($thumbPath));
        } catch (\Throwable) {
            $thumbPath = null;
        }

        DviMedia::create([
            'dvi_voce_id'      => $voce->id,
            'dvi_ispezione_id' => $this->ispezione->id,
            'tipo'             => TipoDviMedia::Foto,
            'percorso'         => $path,
            'nome_file'        => $nome,
            'mime_type'        => $this->fotoUpload->getMimeType(),
            'dimensione_bytes' => $this->fotoUpload->getSize(),
            'thumbnail_path'   => $thumbPath,
            'user_id'          => auth()->id(),
        ]);

        $this->fotoUpload = null;
        $this->fotoVoceId = null;
        $this->ispezione->refresh();
        $this->ispezione->load('voci.media');
    }

    public function eliminaMedia(int $mediaId): void
    {
        $media = DviMedia::where('id', $mediaId)
            ->where('dvi_ispezione_id', $this->ispezione->id)
            ->first();

        if (! $media) return;

        Storage::disk('local')->delete($media->percorso);
        if ($media->thumbnail_path) {
            Storage::disk('local')->delete($media->thumbnail_path);
        }
        $media->delete();

        $this->ispezione->refresh();
        $this->ispezione->load('voci.media');
    }

    public function riordina(array $ordine): void
    {
        foreach ($ordine as $i => $voceId) {
            DviVoce::where('id', $voceId)
                ->where('dvi_ispezione_id', $this->ispezione->id)
                ->update(['ordinamento' => $i]);
        }
        $this->ispezione->refresh();
        $this->ispezione->load('voci.media');
    }

    public function invia(): void
    {
        if (! $this->ispezione || $this->ispezione->voci->isEmpty()) {
            session()->flash('error', 'Aggiungi almeno una voce prima di inviare.');
            return;
        }

        $token = Str::random(64);

        $this->ispezione->update([
            'stato'         => StatoDviIspezione::InviataCliente,
            'link_token'    => $token,
            'link_scade_at' => now()->addDays(7),
            'inviata_at'    => now(),
            'note_meccanico' => $this->noteMeccanico,
        ]);

        InviaDviCliente::dispatch($this->ispezione);

        session()->flash('success', 'DVI inviata al cliente.');
        $this->redirect(route('commesse.show', $this->commessa->id));
    }

    private function chiudiFormVoce(): void
    {
        $this->showFormVoce  = false;
        $this->editingVoceId = null;
        $this->resetFormVoce();
    }

    private function resetFormVoce(): void
    {
        $this->vCategoria    = '';
        $this->vDescrizone   = '';
        $this->vUrgenza      = 'ok';
        $this->vPrezzoStimato = '';
        $this->vNote         = '';
    }

    public function render()
    {
        $categorie = DviCategoria::attive()->pluck('nome')->toArray();

        return view('livewire.dvi.crea-dvi', [
            'categoriePredefinite' => $categorie,
        ])->layout('layouts.tablet', [
            'title'    => 'Nuova DVI — ' . $this->commessa->numero,
            'subtitle' => 'Digital Vehicle Inspection',
        ]);
    }
}
