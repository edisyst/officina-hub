<?php

namespace App\Livewire\Marcatempo;

use App\Actions\Lavorazione\AvviaLavorazioneAction;
use App\Actions\Lavorazione\FermaLavorazioneAction;
use App\Models\Commessa;
use App\Models\Lavorazione;
use App\Models\Ponte;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GestioneLavorazioni extends Component
{
    public Commessa $commessa;

    public bool $showModal    = false;
    public ?int $lavorazioneId = null;

    public string $descrizione         = '';
    public int    $minuti_preventivati = 0;
    public ?int   $user_id             = null;
    public ?int   $ponte_id            = null;
    public ?int   $commessa_riga_id    = null;
    public string $note                = '';

    public string $erroreAvvia = '';

    public function mount(int $commessaId): void
    {
        $this->commessa = Commessa::findOrFail($commessaId);
        $this->user_id  = auth()->id();
    }

    public function apriNuova(): void
    {
        $this->reset(['lavorazioneId', 'descrizione', 'minuti_preventivati', 'ponte_id', 'commessa_riga_id', 'note']);
        $this->user_id            = auth()->id();
        $this->minuti_preventivati = 0;
        $this->showModal          = true;
    }

    public function salva(): void
    {
        $this->validate([
            'descrizione'         => ['required', 'string', 'max:255'],
            'minuti_preventivati' => ['required', 'integer', 'min:0'],
            'user_id'             => ['required', 'exists:users,id'],
            'ponte_id'            => ['nullable', 'exists:ponti,id'],
            'commessa_riga_id'    => ['nullable', 'exists:commessa_righe,id'],
            'note'                => ['nullable', 'string'],
        ]);

        $data = [
            'commessa_id'         => $this->commessa->id,
            'descrizione'         => $this->descrizione,
            'minuti_preventivati' => $this->minuti_preventivati,
            'user_id'             => $this->user_id,
            'ponte_id'            => $this->ponte_id,
            'commessa_riga_id'    => $this->commessa_riga_id,
            'note'                => $this->note ?: null,
        ];

        if ($this->lavorazioneId) {
            Lavorazione::findOrFail($this->lavorazioneId)->update($data);
        } else {
            Lavorazione::create($data);
        }

        $this->showModal = false;
        $this->reset(['lavorazioneId', 'descrizione', 'minuti_preventivati', 'ponte_id', 'commessa_riga_id', 'note']);
    }

    public function avvia(int $id): void
    {
        $this->erroreAvvia = '';
        $lavorazione = Lavorazione::findOrFail($id);

        try {
            app(AvviaLavorazioneAction::class)->execute($lavorazione, auth()->user());
        } catch (ValidationException $e) {
            $this->erroreAvvia = collect($e->errors())->flatten()->first();
        }
    }

    public function ferma(int $id): void
    {
        app(FermaLavorazioneAction::class)->execute(Lavorazione::findOrFail($id));
    }

    public function render()
    {
        $lavorazioni = Lavorazione::with(['meccanico', 'ponte'])
            ->where('commessa_id', $this->commessa->id)
            ->orderBy('created_at')
            ->get();

        $totMinPreventivati = $lavorazioni->sum('minuti_preventivati');
        $totMinEffettivi    = $lavorazioni->whereNotNull('minuti_effettivi')->sum('minuti_effettivi');
        $deltaMinuti        = $totMinEffettivi - $totMinPreventivati;

        // Ore fatturabili dalle righe manodopera (quantita = ore)
        $oreFatturabili = $this->commessa->righe
            ->filter(fn($r) => $r->tipo === \App\Enums\TipoRiga::Manodopera)
            ->sum(fn($r) => (float) $r->quantita);

        $meccanici = User::role('meccanico')->get();
        $ponti     = Ponte::attivi()->get();
        $righe     = $this->commessa->righe;

        return view('livewire.marcatempo.gestione-lavorazioni', compact(
            'lavorazioni',
            'totMinPreventivati',
            'totMinEffettivi',
            'deltaMinuti',
            'oreFatturabili',
            'meccanici',
            'ponti',
            'righe',
        ));
    }
}
