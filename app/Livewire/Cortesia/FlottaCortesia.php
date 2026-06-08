<?php

namespace App\Livewire\Cortesia;

use App\Models\VeicoloCortesia;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FlottaCortesia extends Component
{
    use WithFileUploads;

    public bool $showModal = false;
    public ?int $veicoloId = null;

    public string $targa            = '';
    public string $marca            = '';
    public string $modello          = '';
    public string $anno             = '';
    public string $colore           = '';
    public string $tipo             = 'auto';
    public int    $km_attuali       = 0;
    public string $carburante_tipo  = 'benzina';
    public int    $livello_carburante_inizio = 100;
    public string $note             = '';
    public bool   $attivo           = true;
    public        $immagine         = null;

    protected function rules(): array
    {
        return [
            'targa'                     => ['required', 'string', 'max:20'],
            'marca'                     => ['required', 'string', 'max:100'],
            'modello'                   => ['required', 'string', 'max:100'],
            'anno'                      => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'colore'                    => ['nullable', 'string', 'max:50'],
            'tipo'                      => ['required', 'in:auto,moto,furgone'],
            'km_attuali'                => ['required', 'integer', 'min:0'],
            'carburante_tipo'           => ['required', 'in:benzina,diesel,ibrido,elettrico,gpl,metano'],
            'livello_carburante_inizio' => ['required', 'integer', 'min:0', 'max:100'],
            'note'                      => ['nullable', 'string'],
            'attivo'                    => ['boolean'],
            'immagine'                  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function apriModalNuovo(): void
    {
        $this->reset(['veicoloId', 'targa', 'marca', 'modello', 'anno', 'colore', 'note', 'immagine']);
        $this->tipo                      = 'auto';
        $this->km_attuali                = 0;
        $this->carburante_tipo           = 'benzina';
        $this->livello_carburante_inizio = 100;
        $this->attivo                    = true;
        $this->showModal                 = true;
    }

    public function apriModalModifica(int $id): void
    {
        $v = VeicoloCortesia::findOrFail($id);
        $this->veicoloId                 = $v->id;
        $this->targa                     = $v->targa;
        $this->marca                     = $v->marca;
        $this->modello                   = $v->modello;
        $this->anno                      = (string) ($v->anno ?? '');
        $this->colore                    = $v->colore ?? '';
        $this->tipo                      = $v->tipo;
        $this->km_attuali                = $v->km_attuali;
        $this->carburante_tipo           = $v->carburante_tipo;
        $this->livello_carburante_inizio = $v->livello_carburante_inizio;
        $this->note                      = $v->note ?? '';
        $this->attivo                    = $v->attivo;
        $this->immagine                  = null;
        $this->showModal                 = true;
    }

    public function salva(): void
    {
        $this->authorize('create', VeicoloCortesia::class);
        $this->validate();

        $data = [
            'targa'                     => strtoupper(trim($this->targa)),
            'marca'                     => $this->marca,
            'modello'                   => $this->modello,
            'anno'                      => $this->anno ?: null,
            'colore'                    => $this->colore ?: null,
            'tipo'                      => $this->tipo,
            'km_attuali'                => $this->km_attuali,
            'carburante_tipo'           => $this->carburante_tipo,
            'livello_carburante_inizio' => $this->livello_carburante_inizio,
            'note'                      => $this->note ?: null,
            'attivo'                    => $this->attivo,
        ];

        if ($this->immagine) {
            $path = $this->immagine->store('allegati/cortesia', 'local');
            $data['immagine_path'] = $path;
        }

        if ($this->veicoloId) {
            VeicoloCortesia::findOrFail($this->veicoloId)->update($data);
        } else {
            VeicoloCortesia::create($data);
        }

        $this->showModal = false;
        session()->flash('success', 'Veicolo salvato.');
    }

    public function toggleAttivo(int $id): void
    {
        $this->authorize('update', VeicoloCortesia::findOrFail($id));
        $v = VeicoloCortesia::findOrFail($id);
        $v->update(['attivo' => ! $v->attivo]);
    }

    public function elimina(int $id): void
    {
        $this->authorize('delete', VeicoloCortesia::findOrFail($id));
        VeicoloCortesia::findOrFail($id)->delete();
        session()->flash('success', 'Veicolo eliminato.');
    }

    public function render()
    {
        $veicoli = VeicoloCortesia::withCount(['prestiti', 'prestitiAttivi'])
            ->withSum('prestiti', 'km_percorsi')
            ->orderBy('targa')
            ->get()
            ->map(function ($v) {
                // km percorsi in prestito = somma (km_rientro - km_consegna) dei prestiti rientrati
                $v->km_totali_prestito = $v->prestiti()
                    ->where('stato', 'rientrato')
                    ->whereNotNull('km_rientro')
                    ->selectRaw('SUM(km_rientro - km_consegna) as totale')
                    ->value('totale') ?? 0;
                return $v;
            });

        return view('livewire.cortesia.flotta-cortesia', compact('veicoli'));
    }
}
