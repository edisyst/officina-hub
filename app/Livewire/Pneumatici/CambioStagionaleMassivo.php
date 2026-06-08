<?php

namespace App\Livewire\Pneumatici;

use App\Enums\StagionePneumatico;
use App\Enums\StatoAppuntamento;
use App\Enums\StatoPneumatico;
use App\Jobs\InviaNotificaCambioStagionale;
use App\Models\Appuntamento;
use App\Models\Pneumatico;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CambioStagionaleMassivo extends Component
{
    use WithPagination;

    public string $filtraStagioneTarget = 'estivo';  // stagione da montare
    public string $filtroCliente        = '';

    // Selezione righe
    public array $selezionati = [];
    public bool  $tuttiSelezionati = false;

    // Modal appuntamenti in blocco
    public bool   $showModalAppuntamenti = false;
    public string $settimanaScelta       = '';
    public string $oraInizio             = '08:00';
    public string $durataMinuti          = '60';

    public function mount(): void
    {
        $this->settimanaScelta = now()->startOfWeek()->format('Y-m-d');
    }

    public function updatedFiltraStagioneTarget(): void
    {
        $this->selezionati     = [];
        $this->tuttiSelezionati = false;
        $this->resetPage();
    }

    public function updatedFiltroCliente(): void
    {
        $this->selezionati     = [];
        $this->tuttiSelezionati = false;
        $this->resetPage();
    }

    public function toggleSelezionato(int $id): void
    {
        if (in_array($id, $this->selezionati)) {
            $this->selezionati = array_values(array_diff($this->selezionati, [$id]));
        } else {
            $this->selezionati[] = $id;
        }
    }

    public function selezionaTutti(bool $stato): void
    {
        $this->tuttiSelezionati = $stato;
        if ($stato) {
            $this->selezionati = $this->queryBase()->pluck('id')->toArray();
        } else {
            $this->selezionati = [];
        }
    }

    public function inviaNotifiche(): void
    {
        if (empty($this->selezionati)) return;

        dispatch(new InviaNotificaCambioStagionale($this->selezionati));

        session()->flash('success', 'Notifiche accoda per ' . count($this->selezionati) . ' clienti.');
        $this->selezionati     = [];
        $this->tuttiSelezionati = false;
    }

    public function apriModalAppuntamenti(): void
    {
        if (empty($this->selezionati)) return;
        $this->showModalAppuntamenti = true;
    }

    public function creaAppuntamentiInBlocco(): void
    {
        $this->validate([
            'settimanaScelta' => ['required', 'date'],
            'oraInizio'       => ['required', 'date_format:H:i'],
            'durataMinuti'    => ['required', 'integer', 'min:15', 'max:480'],
        ]);

        $pneumatici = Pneumatico::whereIn('id', $this->selezionati)
            ->where('stato', StatoPneumatico::InDeposito)
            ->with(['cliente', 'veicolo'])
            ->get();

        if ($pneumatici->isEmpty()) {
            $this->showModalAppuntamenti = false;
            return;
        }

        // Orario da settings
        $orarioApertura  = setting('orario_apertura', '08:00');
        $orarioChiusura  = setting('orario_chiusura', '18:00');
        $durata          = (int)$this->durataMinuti;

        $inizioSettimana = Carbon::parse($this->settimanaScelta)->startOfWeek();
        // Costruisci slot per i 5 giorni lavorativi della settimana
        $giorni = collect();
        for ($i = 0; $i < 5; $i++) {
            $giorni->push($inizioSettimana->copy()->addDays($i));
        }

        // Conta appuntamenti esistenti per bilanciare il carico
        $conteggioPerGiorno = [];
        foreach ($giorni as $giorno) {
            $conteggioPerGiorno[$giorno->format('Y-m-d')] = Appuntamento::whereDate('data_ora_inizio', $giorno)->count();
        }

        foreach ($pneumatici as $p) {
            // Scegli il giorno con meno appuntamenti
            $giornoTarget = collect($conteggioPerGiorno)->sortKeys()->keys()->first();
            asort($conteggioPerGiorno);
            $giornoTarget = array_key_first($conteggioPerGiorno);

            $dataInizio = Carbon::parse($giornoTarget . ' ' . $this->oraInizio);

            // Evita orari fuori apertura
            $chiusura = Carbon::parse($giornoTarget . ' ' . $orarioChiusura);
            if ($dataInizio->copy()->addMinutes($durata)->gt($chiusura)) {
                $dataInizio = Carbon::parse($giornoTarget . ' ' . $orarioApertura);
            }

            Appuntamento::create([
                'cliente_id'     => $p->cliente_id,
                'veicolo_id'     => $p->veicolo_id,
                'titolo'         => 'Cambio gomme — ' . ($p->veicolo->targa ?? ''),
                'data_ora_inizio' => $dataInizio,
                'data_ora_fine'  => $dataInizio->copy()->addMinutes($durata),
                'stato'          => StatoAppuntamento::Confermato,
                'user_id'        => auth()->id(),
                'note'           => 'Creato automaticamente da cambio stagionale massivo. Set: ' . $p->stagione->label() . ' ' . $p->misura,
            ]);

            $conteggioPerGiorno[$giornoTarget]++;
        }

        $this->showModalAppuntamenti = false;
        $this->selezionati           = [];
        $this->tuttiSelezionati      = false;

        session()->flash('success', 'Appuntamenti creati per ' . $pneumatici->count() . ' clienti.');
    }

    private function queryBase()
    {
        // La stagione in deposito è quella OPPOSTA alla stagione target da montare
        $stagionaInDeposito = StagionePneumatico::from($this->filtraStagioneTarget)->opposta();

        return Pneumatico::where('stato', StatoPneumatico::InDeposito)
            ->where('stagione', $stagionaInDeposito)
            ->with(['cliente', 'veicolo', 'movimenti'])
            ->when($this->filtroCliente, function ($q) {
                $q->whereHas('cliente', fn($c) =>
                    $c->where('nome', 'like', '%' . $this->filtroCliente . '%')
                      ->orWhere('cognome', 'like', '%' . $this->filtroCliente . '%')
                      ->orWhere('ragione_sociale', 'like', '%' . $this->filtroCliente . '%')
                      ->orWhereHas('veicoli', fn($v) => $v->where('targa', 'like', '%' . $this->filtroCliente . '%'))
                );
            });
    }

    public function render()
    {
        $pneumatici = $this->queryBase()->paginate(20);

        $stagioni = [
            StagionePneumatico::Estivo->value    => StagionePneumatico::Estivo->label(),
            StagionePneumatico::Invernale->value => StagionePneumatico::Invernale->label(),
        ];

        return view('livewire.pneumatici.cambio-stagionale-massivo', compact('pneumatici', 'stagioni'));
    }
}
