<?php

namespace App\Livewire\Cortesia;

use App\Enums\StatoPrestito;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PrestitoCortesia;
use App\Models\VeicoloCortesia;
use App\Services\PdfService;
use Livewire\Component;

class ConsegnaVeicolo extends Component
{
    public int    $step               = 1;
    public ?int   $prestitoId         = null;
    public ?int   $commessaId         = null;

    // Step 1
    public string $data_consegna         = '';
    public string $data_rientro_prevista = '';

    // Step 2
    public ?int   $veicolo_cortesia_id  = null;
    public ?int   $cliente_id           = null;
    public int    $km_consegna          = 0;
    public int    $carburante_consegna  = 100;
    public string $cauzione_importo     = '0.00';
    public bool   $cauzione_pagata      = false;
    public string $note_consegna        = '';

    // Step 3
    public string $firma_consegna_svg = '';

    // Disponibilità calcolata allo step 1
    public array $veicoliDisponibili = [];

    public string $clienteSearch  = '';

    public function mount(?int $commessaId = null): void
    {
        $this->commessaId          = $commessaId;
        $this->data_consegna       = now()->format('Y-m-d\TH:i');
        $this->data_rientro_prevista = now()->addDay()->format('Y-m-d');

        // Pre-compila cliente dalla commessa
        if ($commessaId) {
            $commessa = Commessa::with('cliente')->find($commessaId);
            if ($commessa) {
                $this->cliente_id = $commessa->cliente_id;
            }
        }
    }

    public function avanti(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'data_consegna'         => ['required', 'date'],
                'data_rientro_prevista' => ['required', 'date', 'after_or_equal:data_consegna'],
            ]);
            $this->calcolaDisponibilita();
            $this->step = 2;
        } elseif ($this->step === 2) {
            $this->validate([
                'veicolo_cortesia_id'  => ['required', 'exists:veicoli_cortesia,id'],
                'cliente_id'           => ['required', 'exists:clienti,id'],
                'km_consegna'          => ['required', 'integer', 'min:0'],
                'carburante_consegna'  => ['required', 'integer', 'min:0', 'max:100'],
                'cauzione_importo'     => ['required', 'numeric', 'min:0'],
            ]);
            $this->step = 3;
        }
    }

    public function indietro(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function selezionaVeicolo(int $id): void
    {
        $this->veicolo_cortesia_id = $id;
        $v = VeicoloCortesia::find($id);
        if ($v) {
            $this->km_consegna         = $v->km_attuali;
            $this->carburante_consegna = $v->livello_carburante_inizio;
        }
    }

    public function conferma(): void
    {
        $this->validate(['firma_consegna_svg' => ['required', 'string', 'min:50']]);

        $prestito = PrestitoCortesia::create([
            'veicolo_cortesia_id'   => $this->veicolo_cortesia_id,
            'commessa_id'           => $this->commessaId,
            'cliente_id'            => $this->cliente_id,
            'user_id_consegna'      => auth()->id(),
            'data_consegna'         => $this->data_consegna,
            'data_rientro_prevista' => $this->data_rientro_prevista,
            'km_consegna'           => $this->km_consegna,
            'carburante_consegna'   => $this->carburante_consegna,
            'cauzione_importo'      => $this->cauzione_importo,
            'cauzione_pagata'       => $this->cauzione_pagata,
            'note_consegna'         => $this->note_consegna ?: null,
            'firma_consegna_svg'    => $this->firma_consegna_svg,
            'stato'                 => StatoPrestito::InCorso,
        ]);

        $this->prestitoId = $prestito->id;
        $this->step = 4;
    }

    private function calcolaDisponibilita(): void
    {
        $dal = new \DateTime($this->data_consegna);
        $al  = new \DateTime($this->data_rientro_prevista . ' 23:59:59');

        $this->veicoliDisponibili = VeicoloCortesia::attivi()
            ->get()
            ->filter(fn($v) => $v->isDisponibile($dal, $al))
            ->values()
            ->toArray();
    }

    public function render()
    {
        $clienti = collect();
        if (strlen($this->clienteSearch) >= 2) {
            $clienti = Cliente::search($this->clienteSearch)->limit(10)->get();
        }

        $veicoloSelezionato = $this->veicolo_cortesia_id
            ? VeicoloCortesia::find($this->veicolo_cortesia_id)
            : null;

        $clienteSelezionato = $this->cliente_id
            ? Cliente::find($this->cliente_id)
            : null;

        return view('livewire.cortesia.consegna-veicolo', compact(
            'clienti', 'veicoloSelezionato', 'clienteSelezionato'
        ))->layout('layouts.tablet');
    }
}
