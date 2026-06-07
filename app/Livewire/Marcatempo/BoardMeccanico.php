<?php

namespace App\Livewire\Marcatempo;

use App\Actions\Lavorazione\AvviaLavorazioneAction;
use App\Actions\Lavorazione\FermaLavorazioneAction;
use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Models\Lavorazione;
use App\Models\Ponte;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BoardMeccanico extends Component
{
    // Modal nota
    public bool $showNotaModal = false;
    public ?int $lavorazioneNotaId = null;
    public string $notaTesto = '';

    // QR scanner
    public bool $showQrScanner = false;

    public function avvia(int $lavorazioneId): void
    {
        $lavorazione = Lavorazione::findOrFail($lavorazioneId);
        $this->authorize('avvia', $lavorazione);

        try {
            app(AvviaLavorazioneAction::class)->execute($lavorazione, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('avvia', $e->getMessage());
        }
    }

    public function ferma(int $lavorazioneId): void
    {
        $lavorazione = Lavorazione::findOrFail($lavorazioneId);
        $this->authorize('ferma', $lavorazione);

        app(FermaLavorazioneAction::class)->execute($lavorazione);
    }

    public function apriNotaModal(int $lavorazioneId): void
    {
        $lavorazione = Lavorazione::findOrFail($lavorazioneId);
        $this->lavorazioneNotaId = $lavorazione->id;
        $this->notaTesto = $lavorazione->note ?? '';
        $this->showNotaModal = true;
    }

    public function salvaNota(): void
    {
        $this->validate(['notaTesto' => ['nullable', 'string', 'max:1000']]);

        Lavorazione::findOrFail($this->lavorazioneNotaId)->update(['note' => $this->notaTesto ?: null]);

        $this->showNotaModal = false;
        $this->lavorazioneNotaId = null;
        $this->notaTesto = '';
    }

    public function render()
    {
        $user = auth()->user();

        $query = Commessa::with(['cliente', 'veicolo', 'lavorazioni.meccanico', 'lavorazioni.ponte'])
            ->where('stato', StatoCommessa::InLavorazione);

        if (! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $commesse = $query->orderBy('numero')->get();

        $ponti = Ponte::attivi()->get();

        return view('livewire.marcatempo.board-meccanico', compact('commesse', 'ponti'));
    }
}
