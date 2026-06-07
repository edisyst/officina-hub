<?php

namespace App\Livewire\Dvi;

use App\Enums\StatoApprovazioneDvi;
use App\Enums\StatoDviIspezione;
use App\Models\CommessaRiga;
use App\Models\DviIspezione;
use App\Models\DviVoce;
use Livewire\Component;

class DettaglioDvi extends Component
{
    public int $commessaId;

    public bool $showModalRisposta  = false;
    public ?int $ispezioneSelezionataId = null;

    public function mount(int $commessaId): void
    {
        $this->commessaId = $commessaId;
    }

    public function vediRisposta(int $ispezioneId): void
    {
        $this->ispezioneSelezionataId = $ispezioneId;
        $this->showModalRisposta = true;
    }

    public function convertiInPreventivo(int $ispezioneId): void
    {
        $ispezione = DviIspezione::where('id', $ispezioneId)
            ->where('commessa_id', $this->commessaId)
            ->with('voci')
            ->firstOrFail();

        $vociApprovate = $ispezione->voci->filter(
            fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Approvato
        );

        if ($vociApprovate->isEmpty()) {
            session()->flash('error', 'Nessuna voce approvata da convertire.');
            return;
        }

        $maxOrd = CommessaRiga::where('commessa_id', $this->commessaId)->max('ordinamento') ?? 0;

        foreach ($vociApprovate as $i => $voce) {
            CommessaRiga::create([
                'commessa_id'     => $this->commessaId,
                'tipo'            => \App\Enums\TipoRiga::Manodopera,
                'descrizione'     => '[DVI] ' . $voce->descrizione,
                'quantita'        => 1,
                'prezzo_unitario' => $voce->prezzo_stimato ?? 0,
                'aliquota_iva'    => setting('iva_default', 22),
                'ordinamento'     => $maxOrd + $i + 1,
            ]);
        }

        session()->flash('success', count($vociApprovate) . ' righe aggiunte al preventivo.');
        $this->dispatch('righe-aggiornate');
    }

    public function render()
    {
        $ispezioni = DviIspezione::where('commessa_id', $this->commessaId)
            ->with('voci')
            ->orderByDesc('created_at')
            ->get();

        $ispezioneSelezionata = $this->ispezioneSelezionataId
            ? DviIspezione::with('voci.media')->find($this->ispezioneSelezionataId)
            : null;

        $haInviata = $ispezioni->contains(
            fn($i) => $i->stato === StatoDviIspezione::InviataCliente
        );

        return view('livewire.dvi.dettaglio-dvi', compact(
            'ispezioni', 'haInviata', 'ispezioneSelezionata'
        ));
    }
}
