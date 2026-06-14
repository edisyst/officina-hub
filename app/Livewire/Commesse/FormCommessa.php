<?php

namespace App\Livewire\Commesse;

use App\Actions\Commessa\ApplicaPacchettoAction;
use App\Actions\Commessa\GeneraNumeroProgressivoAction;
use App\Enums\TipoCommessa;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PacchettoServizio;
use App\Models\Veicolo;
use Livewire\Attributes\Rule;
use Livewire\Component;

class FormCommessa extends Component
{
    public string $searchCliente = '';
    public ?int $clienteSelezionatoId = null;
    public ?Cliente $clienteSelezionato = null;

    public string $searchVeicolo = '';
    public ?int $veicoloSelezionatoId = null;
    public ?Veicolo $veicoloSelezionato = null;

    public bool $showNuovoVeicolo = false;

    #[Rule('required|in:meccanica,carrozzeria,tagliando')]
    public string $tipo = 'meccanica';

    #[Rule('nullable|integer|min:0')]
    public ?int $km_ingresso = null;

    #[Rule('required|string')]
    public string $descrizione_cliente = '';

    #[Rule('nullable|date')]
    public ?string $data_uscita_prevista = null;

    // Campi per nuovo veicolo al volo
    #[Rule('nullable|required_if:showNuovoVeicolo,true|in:auto,moto')]
    public string $nv_tipo = 'auto';

    #[Rule('nullable|string|max:20')]
    public ?string $nv_targa = null;

    #[Rule('nullable|required_if:showNuovoVeicolo,true|string|max:100')]
    public string $nv_marca = '';

    #[Rule('nullable|required_if:showNuovoVeicolo,true|string|max:100')]
    public string $nv_modello = '';

    #[Rule('nullable|in:benzina,diesel,ibrido,elettrico,gpl,metano')]
    public string $nv_alimentazione = 'benzina';

    // Preventivo rapido
    public bool $showPreventivoRapido = false;
    public string $cercaPacchettoRapido = '';
    public array $suggerimentiPacchettiRapidi = [];
    public ?int $pacchettoRapidoId = null;
    public array $righePreventivo = [];
    public float $totalePreventivoRapido = 0;

    public function selezionaCliente(int $id): void
    {
        $this->clienteSelezionatoId = $id;
        $this->clienteSelezionato = Cliente::find($id);
        $this->searchCliente = $this->clienteSelezionato->nome_completo;
    }

    public function selezionaVeicolo(int $id): void
    {
        $this->veicoloSelezionatoId = $id;
        $this->veicoloSelezionato = Veicolo::find($id);
        $this->searchVeicolo = $this->veicoloSelezionato->descrizione . ' (' . ($this->veicoloSelezionato->targa ?? 'N/T') . ')';
        // Resetta il pacchetto scelto se non più compatibile
        $this->resetPreventivoRapido();
    }

    // --- Preventivo rapido ---

    public function togglePreventivoRapido(): void
    {
        $this->showPreventivoRapido = ! $this->showPreventivoRapido;
        if (! $this->showPreventivoRapido) {
            $this->resetPreventivoRapido();
        }
    }

    private function resetPreventivoRapido(): void
    {
        $this->cercaPacchettoRapido       = '';
        $this->suggerimentiPacchettiRapidi = [];
        $this->pacchettoRapidoId          = null;
        $this->righePreventivo            = [];
        $this->totalePreventivoRapido     = 0;
    }

    public function updatedCercaPacchettoRapido(): void
    {
        if (strlen($this->cercaPacchettoRapido) < 2) {
            $this->suggerimentiPacchettiRapidi = [];
            return;
        }

        // Costruisce una mini-commessa virtuale per il filtro compatibilità
        $veicolo = $this->veicoloSelezionatoId ? Veicolo::find($this->veicoloSelezionatoId) : null;
        $tipoCommessa = $this->tipo;

        $this->suggerimentiPacchettiRapidi = PacchettoServizio::attivi()
            ->search($this->cercaPacchettoRapido)
            ->with('righe')
            ->orderByDesc('utilizzi')
            ->get()
            ->filter(function ($p) use ($tipoCommessa, $veicolo) {
                if ($p->tipo_commessa !== 'entrambi' && $p->tipo_commessa !== $tipoCommessa) {
                    return false;
                }
                if (! $veicolo) {
                    return true;
                }
                if ($p->tipo_veicolo !== 'entrambi' && $p->tipo_veicolo !== $veicolo->tipo?->value) {
                    return false;
                }
                if ($p->alimentazione !== 'tutte' && $p->alimentazione !== $veicolo->alimentazione?->value) {
                    return false;
                }
                return true;
            })
            ->map(fn($p) => [
                'id'          => $p->id,
                'nome'        => $p->nome,
                'righe_count' => $p->righe->count(),
                'totale'      => $p->calcolaTotale(),
                'utilizzi'    => $p->utilizzi,
            ])
            ->values()
            ->toArray();
    }

    public function selezionaPacchettoRapido(int $id): void
    {
        $pacchetto = PacchettoServizio::with('righe')->findOrFail($id);

        $this->pacchettoRapidoId  = $pacchetto->id;
        $this->cercaPacchettoRapido = $pacchetto->nome;

        $this->righePreventivo = $pacchetto->righe->map(fn($r) => [
            'tipo'               => $r->tipo,
            'descrizione'        => $r->descrizione,
            'articolo_id'        => $r->articolo_id,
            'tariffa_manodopera_id' => null,
            'quantita'           => (float) $r->quantita,
            'prezzo_unitario'    => (float) $r->prezzo_unitario,
            'sconto_percentuale' => (float) $r->sconto_percentuale,
            'iva_percentuale'    => (float) $r->iva_percentuale,
        ])->toArray();

        $this->suggerimentiPacchettiRapidi = [];
        $this->calcolaTotalePreventivoRapido();
    }

    public function updatedRighePreventivo(): void
    {
        $this->calcolaTotalePreventivoRapido();
    }

    private function calcolaTotalePreventivoRapido(): void
    {
        $this->totalePreventivoRapido = collect($this->righePreventivo)
            ->where('tipo', '!=', 'nota')
            ->sum(function ($r) {
                $imp = (float) ($r['quantita'] ?? 0) * (float) ($r['prezzo_unitario'] ?? 0)
                    * (1 - (float) ($r['sconto_percentuale'] ?? 0) / 100);
                return $imp * (1 + (float) ($r['iva_percentuale'] ?? 22) / 100);
            });
    }

    // ---

    public function salva()
    {
        $this->validate();

        if (! $this->clienteSelezionatoId) {
            $this->addError('clienteSelezionatoId', 'Seleziona un cliente.');
            return;
        }

        // Crea veicolo al volo se richiesto
        if ($this->showNuovoVeicolo) {
            $veicolo = Veicolo::create([
                'tipo' => $this->nv_tipo,
                'targa' => $this->nv_targa ? strtoupper($this->nv_targa) : null,
                'marca' => $this->nv_marca,
                'modello' => $this->nv_modello,
                'alimentazione' => $this->nv_alimentazione,
                'km_attuali' => $this->km_ingresso,
            ]);

            $veicolo->clienti()->attach($this->clienteSelezionatoId, [
                'proprietario_attuale' => true,
                'data_inizio' => now()->toDateString(),
            ]);

            $this->veicoloSelezionatoId = $veicolo->id;
        }

        if (! $this->veicoloSelezionatoId) {
            $this->addError('veicoloSelezionatoId', 'Seleziona o crea un veicolo.');
            return;
        }

        $numero = app(GeneraNumeroProgressivoAction::class)->execute();

        $commessa = Commessa::create([
            'numero'               => $numero,
            'cliente_id'           => $this->clienteSelezionatoId,
            'veicolo_id'           => $this->veicoloSelezionatoId,
            'tipo'                 => $this->tipo,
            'stato'                => 'bozza',
            'km_ingresso'          => $this->km_ingresso,
            'data_ingresso'        => now(),
            'data_uscita_prevista' => $this->data_uscita_prevista,
            'descrizione_cliente'  => $this->descrizione_cliente,
            'user_id'              => auth()->id(),
        ]);

        // Applica preventivo rapido se presente
        if ($this->pacchettoRapidoId && count($this->righePreventivo) > 0) {
            $pacchetto = PacchettoServizio::find($this->pacchettoRapidoId);
            if ($pacchetto) {
                app(ApplicaPacchettoAction::class)->execute($commessa, $pacchetto, $this->righePreventivo);
            }
        }

        return $this->redirect(route('commesse.show', $commessa->id), navigate: true);
    }

    public function render()
    {
        $suggerimentiClienti = collect();
        if (strlen($this->searchCliente) >= 2 && ! $this->clienteSelezionatoId) {
            $suggerimentiClienti = Cliente::search($this->searchCliente)->limit(8)->get();
        }

        $suggerimentiVeicoli = collect();
        if (strlen($this->searchVeicolo) >= 2 && ! $this->veicoloSelezionatoId && ! $this->showNuovoVeicolo) {
            $suggerimentiVeicoli = Veicolo::search($this->searchVeicolo)->limit(8)->get();
        }

        return view('livewire.commesse.form-commessa', [
            'suggerimentiClienti' => $suggerimentiClienti,
            'suggerimentiVeicoli' => $suggerimentiVeicoli,
            'tipi' => TipoCommessa::cases(),
        ]);
    }
}
