<?php

namespace App\Livewire\Cortesia;

use App\Enums\StatoPrestito;
use App\Models\PrestitoCortesia;
use Livewire\Component;

class RientroVeicolo extends Component
{
    public PrestitoCortesia $prestito;

    public int    $km_rientro          = 0;
    public int    $carburante_rientro  = 100;
    public string $note_rientro        = '';
    public string $firma_rientro_svg   = '';

    public string $erroreKm       = '';
    public string $avvisoCarb     = '';

    public function mount(PrestitoCortesia $prestito): void
    {
        $this->prestito          = $prestito;
        $this->km_rientro        = $prestito->km_consegna;
        $this->carburante_rientro = $prestito->carburante_consegna;
    }

    public function updatedKmRientro(): void
    {
        if ($this->km_rientro < $this->prestito->km_consegna) {
            $this->erroreKm = 'I km di rientro non possono essere inferiori a quelli di consegna (' . $this->prestito->km_consegna . ' km).';
        } else {
            $this->erroreKm = '';
        }
    }

    public function updatedCarburanteRientro(): void
    {
        $delta = $this->carburante_rientro - $this->prestito->carburante_consegna;
        if ($delta < -10) {
            $this->avvisoCarb = 'Il livello carburante è inferiore alla consegna: verificare con il cliente.';
        } else {
            $this->avvisoCarb = '';
        }
    }

    public function confermaRientro(): void
    {
        $this->validate([
            'km_rientro'         => ['required', 'integer', 'min:' . $this->prestito->km_consegna],
            'carburante_rientro' => ['required', 'integer', 'min:0', 'max:100'],
            'note_rientro'       => ['nullable', 'string'],
            'firma_rientro_svg'  => ['required', 'string', 'min:50'],
        ]);

        $this->prestito->update([
            'km_rientro'              => $this->km_rientro,
            'carburante_rientro'      => $this->carburante_rientro,
            'note_rientro'            => $this->note_rientro ?: null,
            'firma_rientro_svg'       => $this->firma_rientro_svg,
            'data_rientro_effettiva'  => now(),
            'user_id_rientro'         => auth()->id(),
            'stato'                   => StatoPrestito::Rientrato,
        ]);

        // Aggiorna km veicolo
        $this->prestito->veicolo->update([
            'km_attuali' => $this->km_rientro,
        ]);

        session()->flash('success', 'Rientro registrato con successo.');
        $this->redirect(route('cortesia.index'), navigate: true);
    }

    public function getKmPercorsiProperty(): int
    {
        return max(0, $this->km_rientro - $this->prestito->km_consegna);
    }

    public function getDeltaCarburanteProperty(): int
    {
        return $this->carburante_rientro - $this->prestito->carburante_consegna;
    }

    public function render()
    {
        return view('livewire.cortesia.rientro-veicolo')
            ->layout('layouts.tablet');
    }
}
