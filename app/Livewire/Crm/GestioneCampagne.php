<?php

namespace App\Livewire\Crm;

use App\Enums\StatoCampagna;
use App\Jobs\InviaCampagnaEmail;
use App\Models\CampagnaEmail;
use App\Services\Crm\SegmentazioneService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GestioneCampagne extends Component
{
    use WithPagination;

    public bool $showModal       = false;
    public bool $showDettaglio   = false;
    public ?int $campagnaId      = null;

    // Form nuova campagna
    public string $nome             = '';
    public string $oggetto          = '';
    public string $corpo            = '';
    public string $segmento_target  = 'tutti';
    public string $pianificata_at   = '';
    public array  $filtro_json      = [];

    // Anteprima destinatari
    public ?int $conteggioDestinatari = null;

    protected function rules(): array
    {
        return [
            'nome'            => 'required|string|max:255',
            'oggetto'         => 'required|string|max:255',
            'corpo'           => 'required|string',
            'segmento_target' => 'required|string|in:tutti,nuovo,attivo,a_rischio,perso,vip,personalizzato',
            'pianificata_at'  => 'nullable|date',
        ];
    }

    public function updatedSegmentoTarget(): void
    {
        $this->aggiornaConteggio();
    }

    public function aggiornaConteggio(): void
    {
        $service = app(SegmentazioneService::class);
        $this->conteggioDestinatari = $service->clientiPerSegmento($this->segmento_target)->count();
    }

    public function apriModal(): void
    {
        $this->reset(['nome', 'oggetto', 'corpo', 'segmento_target', 'pianificata_at', 'campagnaId', 'conteggioDestinatari']);
        $this->segmento_target = 'tutti';
        $this->aggiornaConteggio();
        $this->showModal = true;
    }

    public function chiudiModal(): void
    {
        $this->showModal = false;
    }

    public function salvaBozza(): void
    {
        $this->validate();
        $this->salva(StatoCampagna::Bozza);
        $this->showModal = false;
        session()->flash('success', 'Campagna salvata come bozza.');
    }

    public function pianifica(): void
    {
        $this->validate(array_merge($this->rules(), [
            'pianificata_at' => 'required|date|after:now',
        ]));

        $campagna = $this->salva(StatoCampagna::Pianificata);

        // Accoda il job al momento pianificato
        InviaCampagnaEmail::dispatch($campagna->id)
            ->delay(now()->diffInSeconds(\Carbon\Carbon::parse($this->pianificata_at)));

        $this->showModal = false;
        session()->flash('success', 'Campagna pianificata.');
    }

    public function inviaSubito(): void
    {
        $this->validate();
        $campagna = $this->salva(StatoCampagna::Pianificata);
        InviaCampagnaEmail::dispatch($campagna->id);
        $this->showModal = false;
        session()->flash('success', 'Campagna accodata per invio immediato.');
    }

    private function salva(StatoCampagna $stato): CampagnaEmail
    {
        return CampagnaEmail::create([
            'nome'             => $this->nome,
            'oggetto'          => $this->oggetto,
            'corpo'            => $this->corpo,
            'stato'            => $stato,
            'segmento_target'  => $this->segmento_target,
            'filtro_json'      => $this->filtro_json ?: null,
            'pianificata_at'   => $this->pianificata_at ?: null,
            'user_id'          => Auth::id(),
        ]);
    }

    public function annulla(int $id): void
    {
        $campagna = CampagnaEmail::findOrFail($id);
        if (in_array($campagna->stato, [StatoCampagna::Bozza, StatoCampagna::Pianificata])) {
            $campagna->update(['stato' => StatoCampagna::Annullata]);
            session()->flash('success', 'Campagna annullata.');
        }
    }

    public function apriDettaglio(int $id): void
    {
        $this->campagnaId   = $id;
        $this->showDettaglio = true;
    }

    public function chiudiDettaglio(): void
    {
        $this->showDettaglio = false;
        $this->campagnaId   = null;
    }

    public function render()
    {
        $campagne = CampagnaEmail::with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        $campagnaDettaglio = $this->campagnaId
            ? CampagnaEmail::with(['invii.cliente'])->find($this->campagnaId)
            : null;

        return view('livewire.crm.gestione-campagne', [
            'campagne'          => $campagne,
            'campagnaDettaglio' => $campagnaDettaglio,
            'segmenti'          => [
                'tutti'          => 'Tutti (con consenso)',
                'nuovo'          => 'Nuovi',
                'attivo'         => 'Attivi',
                'a_rischio'      => 'A rischio',
                'perso'          => 'Persi',
                'vip'            => 'VIP',
                'personalizzato' => 'Personalizzato',
            ],
            'variabiliDisponibili' => [
                'NOME_CLIENTE', 'TARGA_VEICOLO',
                'NOME_OFFICINA', 'TELEFONO_OFFICINA', 'EMAIL_OFFICINA',
            ],
        ]);
    }
}
