<?php

namespace App\Livewire\Agenda;

use App\Enums\StatoAppuntamento;
use App\Http\Requests\SalvaAppuntamentoRequest;
use App\Models\Appuntamento;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Ponte;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class CalendarioAppuntamenti extends Component
{
    public bool $showModal = false;
    public ?int $appuntamentoId = null;

    // Campi form
    public string $titolo          = '';
    public string $data_ora_inizio = '';
    public string $data_ora_fine   = '';
    public bool   $tutto_il_giorno = false;
    public string $stato           = 'pianificato';
    public ?int   $ponte_id        = null;
    public ?int   $user_id         = null;
    public ?int   $commessa_id     = null;
    public ?int   $cliente_id      = null;
    public ?int   $veicolo_id      = null;
    public string $note            = '';

    // Ricerca commessa
    public string $commessaSearch = '';

    public function getRules(): array
    {
        return [
            'titolo'          => ['required', 'string', 'max:255'],
            'data_ora_inizio' => ['required', 'date'],
            'data_ora_fine'   => ['required', 'date', 'after_or_equal:data_ora_inizio'],
            'stato'           => ['required'],
            'ponte_id'        => ['nullable', 'exists:ponti,id'],
            'user_id'         => ['nullable', 'exists:users,id'],
            'commessa_id'     => ['nullable', 'exists:commesse,id'],
            'cliente_id'      => ['nullable', 'exists:clienti,id'],
            'veicolo_id'      => ['nullable', 'exists:veicoli,id'],
            'tutto_il_giorno' => ['boolean'],
            'note'            => ['nullable', 'string'],
        ];
    }

    public function apriModalNuovo(string $start = '', string $end = '', ?string $resourceId = null): void
    {
        $this->reset(['appuntamentoId', 'titolo', 'note', 'commessa_id', 'cliente_id', 'veicolo_id', 'commessaSearch']);
        $this->data_ora_inizio = $start ? substr($start, 0, 16) : now()->format('Y-m-d\TH:i');
        $this->data_ora_fine   = $end   ? substr($end, 0, 16)   : now()->addHour()->format('Y-m-d\TH:i');
        $this->stato           = 'pianificato';
        $this->tutto_il_giorno = false;

        // Pre-imposta risorsa da FullCalendar
        $this->ponte_id = null;
        $this->user_id  = null;
        if ($resourceId) {
            if (str_starts_with($resourceId, 'ponte_')) {
                $this->ponte_id = (int) substr($resourceId, 6);
            } elseif (str_starts_with($resourceId, 'mec_')) {
                $this->user_id = (int) substr($resourceId, 4);
            }
        }

        $this->showModal = true;
    }

    public function apriModalModifica(int $id): void
    {
        $app = Appuntamento::findOrFail($id);
        $this->appuntamentoId  = $app->id;
        $this->titolo          = $app->titolo;
        $this->data_ora_inizio = $app->data_ora_inizio->format('Y-m-d\TH:i');
        $this->data_ora_fine   = $app->data_ora_fine->format('Y-m-d\TH:i');
        $this->tutto_il_giorno = $app->tutto_il_giorno;
        $this->stato           = $app->stato->value;
        $this->ponte_id        = $app->ponte_id;
        $this->user_id         = $app->user_id;
        $this->commessa_id     = $app->commessa_id;
        $this->cliente_id      = $app->cliente_id;
        $this->veicolo_id      = $app->veicolo_id;
        $this->note            = $app->note ?? '';
        $this->showModal       = true;
    }

    public function salva(): void
    {
        $this->validate($this->getRules());

        $data = [
            'titolo'          => $this->titolo,
            'data_ora_inizio' => $this->data_ora_inizio,
            'data_ora_fine'   => $this->data_ora_fine,
            'tutto_il_giorno' => $this->tutto_il_giorno,
            'stato'           => $this->stato,
            'ponte_id'        => $this->ponte_id,
            'user_id'         => $this->user_id,
            'commessa_id'     => $this->commessa_id,
            'cliente_id'      => $this->cliente_id,
            'veicolo_id'      => $this->veicolo_id,
            'note'            => $this->note ?: null,
        ];

        if ($this->appuntamentoId) {
            Appuntamento::findOrFail($this->appuntamentoId)->update($data);
        } else {
            Appuntamento::create($data);
        }

        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }

    public function elimina(): void
    {
        if ($this->appuntamentoId) {
            Appuntamento::findOrFail($this->appuntamentoId)->delete();
            $this->showModal = false;
            $this->dispatch('calendar-refresh');
        }
    }

    public function sposta(int $id, string $start, string $end): void
    {
        Appuntamento::findOrFail($id)->update([
            'data_ora_inizio' => $start,
            'data_ora_fine'   => $end,
        ]);
        $this->dispatch('calendar-refresh');
    }

    public function ridimensiona(int $id, string $start, string $end): void
    {
        $this->sposta($id, $start, $end);
    }

    public function render()
    {
        $meccanici = User::role('meccanico')->get();
        $ponti     = Ponte::attivi()->get();
        $stati     = StatoAppuntamento::cases();

        $commesse = collect();
        if (strlen($this->commessaSearch) >= 2) {
            $commesse = Commessa::with(['cliente', 'veicolo'])
                ->search($this->commessaSearch)
                ->limit(10)
                ->get();
        }

        return view('livewire.agenda.calendario-appuntamenti', compact(
            'meccanici', 'ponti', 'stati', 'commesse'
        ));
    }
}
