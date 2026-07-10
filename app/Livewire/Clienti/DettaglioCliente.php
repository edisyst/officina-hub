<?php

namespace App\Livewire\Clienti;

use App\Enums\TipoCrmNota;
use App\Livewire\Concerns\TracksRecentView;
use App\Models\Cliente;
use App\Models\CrmNota;
use App\Models\Veicolo;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DettaglioCliente extends Component
{
    use TracksRecentView;
    public Cliente $cliente;
    public string $searchVeicolo = '';
    public bool $showAssociaModal = false;
    public ?int $veicoloSelezionatoId = null;

    // CRM
    public string $nuovaNota      = '';
    public string $tipoNota       = 'nota';
    public bool   $consensoMarketing = false;

    public function mount(int $clienteId): void
    {
        $this->cliente          = Cliente::withTrashed()->findOrFail($clienteId);
        $this->consensoMarketing = (bool) $this->cliente->consenso_marketing;

        $this->trackRecentView($this->cliente);
    }

    public function dissocia(int $veicoloId): void
    {
        $this->authorize('update', $this->cliente);
        $this->cliente->veicoli()->detach($veicoloId);
        $this->cliente->refresh();
        session()->flash('success', 'Veicolo dissociato.');
    }

    public function apriAssociaModal(): void
    {
        $this->searchVeicolo = '';
        $this->veicoloSelezionatoId = null;
        $this->showAssociaModal = true;
    }

    public function associa(): void
    {
        $this->authorize('update', $this->cliente);

        if (! $this->veicoloSelezionatoId) {
            $this->addError('veicoloSelezionatoId', 'Seleziona un veicolo.');
            return;
        }

        $this->cliente->veicoli()->syncWithoutDetaching([
            $this->veicoloSelezionatoId => [
                'proprietario_attuale' => true,
                'data_inizio' => now()->toDateString(),
            ],
        ]);

        $this->cliente->refresh();
        $this->showAssociaModal = false;
        session()->flash('success', 'Veicolo associato con successo.');
    }

    public function aggiungiNota(): void
    {
        $this->validate([
            'nuovaNota' => 'required|string|max:5000',
            'tipoNota'  => 'required|in:nota,chiamata,email,appuntamento,altro',
        ]);

        CrmNota::create([
            'cliente_id'       => $this->cliente->id,
            'user_id'          => Auth::id(),
            'testo'            => $this->nuovaNota,
            'tipo'             => $this->tipoNota,
            'data_interazione' => now(),
        ]);

        $this->nuovaNota = '';
        $this->tipoNota  = 'nota';
        $this->cliente->refresh();
        session()->flash('success', 'Nota aggiunta.');
    }

    public function eliminaNota(int $notaId): void
    {
        $nota = CrmNota::where('cliente_id', $this->cliente->id)->findOrFail($notaId);
        $nota->delete();
        $this->cliente->refresh();
    }

    public function aggiornaConsenso(): void
    {
        $this->cliente->update([
            'consenso_marketing'    => $this->consensoMarketing,
            'consenso_marketing_at' => $this->consensoMarketing ? now() : null,
        ]);
        session()->flash('success', 'Consenso marketing aggiornato.');
    }

    private function calcolaSpesaMensile(): array
    {
        $docs = $this->cliente->documenti()
            ->select(\Illuminate\Support\Facades\DB::raw('SUM(totale) as totale'), 'data_emissione')
            ->where('data_emissione', '>=', now()->subMonths(12)->startOfMonth())
            ->whereNotNull('data_emissione')
            ->get()
            ->groupBy(fn($d) => \Carbon\Carbon::parse($d->data_emissione)->format('Y-m'));

        $labels = [];
        $valori = [];
        for ($i = 11; $i >= 0; $i--) {
            $k         = now()->subMonths($i)->format('Y-m');
            $labels[]  = now()->subMonths($i)->format('M Y');
            $valori[]  = isset($docs[$k]) ? (float) $docs[$k]->sum('totale') : 0;
        }

        return compact('labels', 'valori');
    }

    public function render()
    {
        $veicoli = $this->cliente->veicoli()->withPivot(['proprietario_attuale', 'data_inizio', 'data_fine'])->get();


        $veicoliDisponibili = Veicolo::query()
            ->when($this->searchVeicolo, fn($q) => $q->search($this->searchVeicolo))
            ->whereNotIn('id', $veicoli->pluck('id'))
            ->limit(10)
            ->get();


        $crmNote = $this->cliente->crmNote()->with('user')->get();

        $spesaMensile = $this->calcolaSpesaMensile();

        $campagneRicevute = $this->cliente->campagnaInvii()
            ->with('campagna')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('livewire.clienti.dettaglio-cliente', compact(
            'veicoli', 'veicoliDisponibili', 'crmNote', 'spesaMensile', 'campagneRicevute'
        ));
    }
}
