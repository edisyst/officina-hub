<?php

namespace App\Livewire\Cortesia;

use App\Enums\StatoPrestito;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PrestitoCortesia;
use App\Models\VeicoloCortesia;
use Livewire\Component;

class CalendarioDisponibilita extends Component
{
    public bool   $showModal   = false;
    public ?int   $prestitoId  = null;

    public ?int    $veicolo_cortesia_id     = null;
    public ?int    $cliente_id              = null;
    public ?int    $commessa_id             = null;
    public string  $data_consegna           = '';
    public string  $data_rientro_prevista   = '';
    public int     $km_consegna             = 0;
    public int     $carburante_consegna     = 100;
    public string  $cauzione_importo        = '0.00';
    public bool    $cauzione_pagata         = false;
    public string  $note_consegna           = '';
    public string  $stato                   = 'prenotato';

    public string $clienteSearch   = '';
    public string $commessaSearch  = '';

    protected function rules(): array
    {
        return [
            'veicolo_cortesia_id'   => ['required', 'exists:veicoli_cortesia,id'],
            'cliente_id'            => ['required', 'exists:clienti,id'],
            'commessa_id'           => ['nullable', 'exists:commesse,id'],
            'data_consegna'         => ['required', 'date'],
            'data_rientro_prevista' => ['required', 'date', 'after_or_equal:data_consegna'],
            'km_consegna'           => ['required', 'integer', 'min:0'],
            'carburante_consegna'   => ['required', 'integer', 'min:0', 'max:100'],
            'cauzione_importo'      => ['required', 'numeric', 'min:0'],
            'cauzione_pagata'       => ['boolean'],
            'note_consegna'         => ['nullable', 'string'],
            'stato'                 => ['required', 'in:prenotato,in_corso,rientrato,annullato'],
        ];
    }

    public function apriModalNuovo(string $start = '', string $resourceId = ''): void
    {
        $this->reset(['prestitoId', 'cliente_id', 'commessa_id', 'note_consegna', 'clienteSearch', 'commessaSearch']);
        $this->data_consegna         = $start ? substr($start, 0, 16) : now()->format('Y-m-d\TH:i');
        $this->data_rientro_prevista = now()->addDay()->format('Y-m-d');
        $this->stato                 = 'prenotato';
        $this->cauzione_importo      = '0.00';
        $this->cauzione_pagata       = false;
        $this->carburante_consegna   = 100;

        $this->veicolo_cortesia_id = null;
        if ($resourceId && str_starts_with($resourceId, 'cortesia_')) {
            $this->veicolo_cortesia_id = (int) substr($resourceId, 9);
            // Pre-compila km dal veicolo
            $v = VeicoloCortesia::find($this->veicolo_cortesia_id);
            $this->km_consegna = $v?->km_attuali ?? 0;
        }

        $this->showModal = true;
    }

    public function apriModalModifica(int $id): void
    {
        $p = PrestitoCortesia::findOrFail($id);
        $this->prestitoId              = $p->id;
        $this->veicolo_cortesia_id     = $p->veicolo_cortesia_id;
        $this->cliente_id              = $p->cliente_id;
        $this->commessa_id             = $p->commessa_id;
        $this->data_consegna           = $p->data_consegna->format('Y-m-d\TH:i');
        $this->data_rientro_prevista   = $p->data_rientro_prevista->format('Y-m-d');
        $this->km_consegna             = $p->km_consegna;
        $this->carburante_consegna     = $p->carburante_consegna;
        $this->cauzione_importo        = number_format($p->cauzione_importo, 2, '.', '');
        $this->cauzione_pagata         = $p->cauzione_pagata;
        $this->note_consegna           = $p->note_consegna ?? '';
        $this->stato                   = $p->stato->value;
        $this->showModal               = true;
    }

    public function salva(): void
    {
        $this->validate();

        $data = [
            'veicolo_cortesia_id'   => $this->veicolo_cortesia_id,
            'cliente_id'            => $this->cliente_id,
            'commessa_id'           => $this->commessa_id,
            'data_consegna'         => $this->data_consegna,
            'data_rientro_prevista' => $this->data_rientro_prevista,
            'km_consegna'           => $this->km_consegna,
            'carburante_consegna'   => $this->carburante_consegna,
            'cauzione_importo'      => $this->cauzione_importo,
            'cauzione_pagata'       => $this->cauzione_pagata,
            'note_consegna'         => $this->note_consegna ?: null,
            'stato'                 => $this->stato,
            'user_id_consegna'      => auth()->id(),
        ];

        if ($this->prestitoId) {
            PrestitoCortesia::findOrFail($this->prestitoId)->update($data);
        } else {
            PrestitoCortesia::create($data);
        }

        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }

    public function elimina(): void
    {
        if ($this->prestitoId) {
            PrestitoCortesia::findOrFail($this->prestitoId)->delete();
            $this->showModal = false;
            $this->dispatch('calendar-refresh');
        }
    }

    public function render()
    {
        $veicoli = VeicoloCortesia::attivi()->orderBy('targa')->get();
        $stati   = StatoPrestito::cases();

        $clienti = collect();
        if (strlen($this->clienteSearch) >= 2) {
            $clienti = Cliente::search($this->clienteSearch)->limit(10)->get();
        }

        $commesse = collect();
        if (strlen($this->commessaSearch) >= 2) {
            $commesse = Commessa::with(['cliente', 'veicolo'])
                ->search($this->commessaSearch)->limit(10)->get();
        }

        return view('livewire.cortesia.calendario-disponibilita', compact(
            'veicoli', 'stati', 'clienti', 'commesse'
        ));
    }
}
