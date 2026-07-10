<?php

namespace App\Livewire\Commesse;

use App\Actions\Commessa\ApplicaPacchettoAction;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\CasaMadre;
use App\Models\Commessa;
use App\Models\CommessaRiga;
use App\Models\Garanzia;
use App\Models\PacchettoServizio;
use App\Models\Setting;
use App\Models\TariffaManodopera;
use App\Models\TariffaOraria;
use App\Services\Pricing\MatricePrezzoService;
use App\Traits\EmitsActionCompleted;
use Livewire\Attributes\Rule;
use Livewire\Component;

class GestioneRighe extends Component
{
    use EmitsActionCompleted;
    public Commessa $commessa;
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|in:manodopera,articolo,nota')]
    public string $tipo = 'articolo';

    // Typeahead articolo
    public string $cercaArticolo = '';
    public array $suggerimentiArticolo = [];
    public ?int $articolo_id = null;
    public int $giacenzaDisponibile = 0;
    public bool $giacenzaInsuffciente = false;

    // Typeahead tariffa (solo per tipo=manodopera)
    public string $cercaTariffa = '';
    public array $suggerimentiTariffe = [];
    public ?int $tariffa_manodopera_id = null;

    // Tariffa oraria nominata
    public ?int $tariffa_oraria_id = null;

    #[Rule('required|string|max:255')]
    public string $descrizione = '';

    #[Rule('required|numeric|min:0')]
    public float $quantita = 1;

    #[Rule('nullable|numeric|min:0')]
    public ?float $ore_preventivate = null;

    #[Rule('required|numeric|min:0')]
    public float $prezzo_unitario = 0;

    #[Rule('numeric|min:0|max:100')]
    public float $sconto_percentuale = 0;

    #[Rule('numeric|min:0|max:100')]
    public float $iva_percentuale = 22;

    // Garanzia
    public bool $inGaranzia      = false;
    public ?int $garanziaId      = null;
    public ?int $casaMadreId     = null;

    // Modal "Applica pacchetto"
    public bool $showPacchettoModal = false;
    public int $passoPacchetto = 1;
    public string $cercaPacchetto = '';
    public array $suggerimentiPacchetti = [];
    public ?int $pacchettoSelezionatoId = null;
    public array $righePreview = [];
    public float $totalePreview = 0;

    public function mount(int $commessaId): void
    {
        $this->commessa = Commessa::with('veicolo')->findOrFail($commessaId);
        $this->iva_percentuale = (float) Setting::get('iva_default', 22);
    }

    public function apriModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->suggerimentiArticolo = [];
        $this->cercaArticolo = '';
        $this->suggerimentiTariffe = [];
        $this->cercaTariffa = '';
        $this->tariffa_manodopera_id = null;
        $this->tariffa_oraria_id = null;

        if ($id) {
            $riga = CommessaRiga::with('articolo')->findOrFail($id);
            $this->fill([
                'tipo'               => $riga->tipo->value,
                'descrizione'        => $riga->descrizione,
                'quantita'           => (float) $riga->quantita,
                'ore_preventivate'   => $riga->ore_preventivate !== null ? (float) $riga->ore_preventivate : null,
                'prezzo_unitario'    => (float) $riga->prezzo_unitario,
                'sconto_percentuale' => (float) $riga->sconto_percentuale,
                'iva_percentuale'    => (float) $riga->iva_percentuale,
                'articolo_id'        => $riga->articolo_id,
                'tariffa_manodopera_id' => $riga->tariffa_manodopera_id,
                'tariffa_oraria_id'  => $riga->tariffa_oraria_id,
                'inGaranzia'         => (bool) $riga->in_garanzia,
                'garanziaId'         => $riga->garanzia_id,
                'casaMadreId'        => $riga->casa_madre_id,
            ]);

            if ($riga->articolo) {
                $this->cercaArticolo = $riga->articolo->codice . ' — ' . $riga->articolo->descrizione;
                $this->giacenzaDisponibile = $riga->articolo->giacenza_attuale;
                $this->aggiornaAvvisoGiacenza();
            }

            if ($riga->tariffa_manodopera_id) {
                $tariffa = TariffaManodopera::find($riga->tariffa_manodopera_id);
                if ($tariffa) {
                    $this->cercaTariffa = $tariffa->codice . ' — ' . $tariffa->descrizione;
                }
            }
        } else {
            $this->reset(['tipo', 'descrizione', 'quantita', 'ore_preventivate', 'prezzo_unitario', 'sconto_percentuale', 'articolo_id', 'giacenzaDisponibile', 'tariffa_manodopera_id', 'tariffa_oraria_id', 'inGaranzia', 'garanziaId', 'casaMadreId']);
            $this->tipo = 'articolo';
            $this->quantita = 1;
            $this->prezzo_unitario = 0;
            $this->iva_percentuale = (float) Setting::get('iva_default', 22);
            $this->giacenzaInsuffciente = false;
        }

        $this->showModal = true;
    }

    public function updatedTipo(): void
    {
        $this->articolo_id = null;
        $this->cercaArticolo = '';
        $this->suggerimentiArticolo = [];
        $this->giacenzaInsuffciente = false;
        $this->tariffa_manodopera_id = null;
        $this->cercaTariffa = '';
        $this->suggerimentiTariffe = [];

        if ($this->tipo === 'manodopera' && ! $this->editingId) {
            $default = TariffaOraria::attive()->where('is_default', true)->first();
            if ($default) {
                $this->tariffa_oraria_id = $default->id;
                $this->prezzo_unitario   = (float) $default->tariffa_oraria;
            } else {
                $this->prezzo_unitario = (float) Setting::get('costo_orario_default', 45);
            }
        }
        if ($this->tipo === 'nota') {
            $this->prezzo_unitario = 0;
            $this->quantita = 1;
        }
    }

    // --- Typeahead articolo ---

    public function updatedCercaArticolo(): void
    {
        if (strlen($this->cercaArticolo) < 2) {
            $this->suggerimentiArticolo = [];
            return;
        }

        $this->suggerimentiArticolo = Articolo::attivi()
            ->search($this->cercaArticolo)
            ->limit(8)
            ->get(['id', 'codice', 'descrizione', 'prezzo_vendita', 'iva_percentuale', 'giacenza_attuale'])
            ->toArray();
    }

    public function selezionaArticolo(int $id): void
    {
        $articolo = Articolo::findOrFail($id);
        $this->articolo_id = $articolo->id;
        $this->cercaArticolo = $articolo->codice . ' — ' . $articolo->descrizione;
        $this->descrizione = $articolo->descrizione;
        $this->prezzo_unitario = (float) $articolo->prezzo_vendita;
        $this->iva_percentuale = (float) $articolo->iva_percentuale;
        $this->giacenzaDisponibile = $articolo->giacenza_attuale;
        $this->suggerimentiArticolo = [];
        $this->aggiornaAvvisoGiacenza();
    }

    // --- Typeahead tariffa ---

    public function updatedCercaTariffa(): void
    {
        if (strlen($this->cercaTariffa) < 2) {
            $this->suggerimentiTariffe = [];
            return;
        }

        $this->suggerimentiTariffe = TariffaManodopera::attivi()
            ->search($this->cercaTariffa)
            ->limit(8)
            ->get(['id', 'codice', 'descrizione', 'categoria', 'minuti_standard', 'prezzo_listino', 'iva_percentuale'])
            ->toArray();
    }

    public function selezionaTariffa(int $id): void
    {
        $tariffa = TariffaManodopera::findOrFail($id);
        $this->tariffa_manodopera_id = $tariffa->id;
        $this->cercaTariffa = $tariffa->codice . ' — ' . $tariffa->descrizione;
        $this->descrizione = $tariffa->descrizione;
        $this->quantita = round($tariffa->minuti_standard / 60, 2);
        $this->prezzo_unitario = (float) $tariffa->prezzo_listino;
        $this->iva_percentuale = (float) $tariffa->iva_percentuale;
        $this->suggerimentiTariffe = [];
    }

    public function updatedTariffaOrariaId(): void
    {
        if ($this->tariffa_oraria_id) {
            $t = TariffaOraria::find($this->tariffa_oraria_id);
            if ($t) {
                $this->prezzo_unitario = (float) $t->tariffa_oraria;
            }
        }
    }

    public function riapplicaMatrice(): void
    {
        if (! $this->articolo_id) return;
        $articolo = \App\Models\Articolo::find($this->articolo_id);
        if (! $articolo) return;
        $suggerito = app(MatricePrezzoService::class)->suggestPrice($articolo->prezzo_acquisto);
        if ($suggerito !== null) {
            $this->prezzo_unitario = (float) $suggerito;
        }
    }

    public function updatedQuantita(): void
    {
        $this->aggiornaAvvisoGiacenza();
    }

    private function aggiornaAvvisoGiacenza(): void
    {
        $this->giacenzaInsuffciente = $this->articolo_id !== null
            && $this->giacenzaDisponibile < (int) ceil($this->quantita);
    }

    public function salva(): void
    {
        $this->validate();
        $this->authorize('update', $this->commessa);

        $dati = [
            'tipo'                  => $this->tipo,
            'articolo_id'           => $this->tipo === 'articolo' ? $this->articolo_id : null,
            'tariffa_manodopera_id' => $this->tipo === 'manodopera' ? $this->tariffa_manodopera_id : null,
            'tariffa_oraria_id'     => $this->tipo === 'manodopera' ? $this->tariffa_oraria_id : null,
            'descrizione'           => $this->descrizione,
            'quantita'              => $this->quantita,
            'ore_preventivate'      => $this->tipo === 'manodopera' ? $this->ore_preventivate : null,
            'prezzo_unitario'       => $this->prezzo_unitario,
            'sconto_percentuale'    => $this->sconto_percentuale,
            'iva_percentuale'       => $this->iva_percentuale,
            'in_garanzia'           => $this->inGaranzia,
            'garanzia_id'           => $this->inGaranzia ? $this->garanziaId : null,
            'casa_madre_id'         => $this->inGaranzia ? $this->casaMadreId : null,
        ];

        if ($this->editingId) {
            CommessaRiga::findOrFail($this->editingId)->update($dati);
        } else {
            $ordinamento = $this->commessa->righe()->max('ordinamento') + 1;
            $riga = $this->commessa->righe()->create(array_merge($dati, ['ordinamento' => $ordinamento]));

            $activityId = activity('commessa_riga')
                ->causedBy(auth()->user())
                ->performedOn($riga)
                ->withProperties([
                    'commessa_id'      => $this->commessa->id,
                    'commessa_numero'  => $this->commessa->numero,
                    'descrizione'      => $riga->descrizione,
                    'quantita'         => (float) $riga->quantita,
                    'undoable'         => true,
                ])
                ->event('created')
                ->log('riga_aggiunta')
                ->id;

            $this->emitActionCompleted("Riga aggiunta: {$riga->descrizione}", $activityId);
        }

        $this->showModal = false;
        $this->commessa->load('righe');
    }

    public function elimina(int $id): void
    {
        $this->authorize('update', $this->commessa);
        CommessaRiga::findOrFail($id)->delete();
        $this->commessa->load('righe');
    }

    public function aggiornaOrdinamento(array $ordine): void
    {
        $this->authorize('update', $this->commessa);
        foreach ($ordine as $indice => $id) {
            CommessaRiga::where('id', $id)->update(['ordinamento' => $indice]);
        }
        $this->commessa->load('righe');
    }

    // --- Applica pacchetto ---

    public function apriPacchettoModal(): void
    {
        $this->reset(['cercaPacchetto', 'suggerimentiPacchetti', 'pacchettoSelezionatoId', 'righePreview', 'totalePreview']);
        $this->passoPacchetto = 1;
        $this->showPacchettoModal = true;
    }

    public function updatedCercaPacchetto(): void
    {
        if (strlen($this->cercaPacchetto) < 2) {
            $this->suggerimentiPacchetti = [];
            return;
        }

        $commessa = $this->commessa;

        $this->suggerimentiPacchetti = PacchettoServizio::attivi()
            ->search($this->cercaPacchetto)
            ->with('righe')
            ->orderByDesc('utilizzi')
            ->get()
            ->filter(fn($p) => $p->isCompatibile($commessa))
            ->map(fn($p) => [
                'id'              => $p->id,
                'nome'            => $p->nome,
                'descrizione'     => $p->descrizione,
                'righe_count'     => $p->righe->count(),
                'totale'          => $p->calcolaTotale(),
                'utilizzi'        => $p->utilizzi,
                'tipo_commessa'   => $p->tipo_commessa,
                'tipo_veicolo'    => $p->tipo_veicolo,
                'alimentazione'   => $p->alimentazione,
            ])
            ->values()
            ->toArray();
    }

    public function selezionaPacchettoPreview(int $id): void
    {
        $pacchetto = PacchettoServizio::with('righe.articolo')->findOrFail($id);

        $this->pacchettoSelezionatoId = $pacchetto->id;
        $this->righePreview = $pacchetto->righe->map(fn($r) => [
            'tipo'               => $r->tipo,
            'descrizione'        => $r->descrizione,
            'articolo_id'        => $r->articolo_id,
            'tariffa_manodopera_id' => null,
            'quantita'           => (float) $r->quantita,
            'prezzo_unitario'    => (float) $r->prezzo_unitario,
            'sconto_percentuale' => (float) $r->sconto_percentuale,
            'iva_percentuale'    => (float) $r->iva_percentuale,
        ])->toArray();

        $this->calcolaTotalePreview();
        $this->passoPacchetto = 2;
        $this->suggerimentiPacchetti = [];
        $this->cercaPacchetto = '';
    }

    public function updatedRighePreview(): void
    {
        $this->calcolaTotalePreview();
    }

    private function calcolaTotalePreview(): void
    {
        $this->totalePreview = collect($this->righePreview)
            ->where('tipo', '!=', 'nota')
            ->sum(function ($r) {
                $imp = (float) ($r['quantita'] ?? 0) * (float) ($r['prezzo_unitario'] ?? 0)
                    * (1 - (float) ($r['sconto_percentuale'] ?? 0) / 100);
                return $imp * (1 + (float) ($r['iva_percentuale'] ?? 22) / 100);
            });
    }

    public function confermaPacchetto(): void
    {
        $this->authorize('update', $this->commessa);

        $pacchetto = PacchettoServizio::findOrFail($this->pacchettoSelezionatoId);

        app(ApplicaPacchettoAction::class)->execute(
            $this->commessa,
            $pacchetto,
            $this->righePreview
        );

        $this->showPacchettoModal = false;
        $this->reset(['cercaPacchetto', 'suggerimentiPacchetti', 'pacchettoSelezionatoId', 'righePreview', 'totalePreview']);
        $this->passoPacchetto = 1;
        $this->commessa->load('righe');

        session()->flash('success', 'Pacchetto "' . $pacchetto->nome . '" applicato (' . count($this->righePreview) . ' righe create).');
    }

    public function toggleOutcome(int $id): void
    {
        $riga = CommessaRiga::findOrFail($id);
        $this->authorize('update', $this->commessa);

        $nuovoOutcome = $riga->outcome === 'declined' ? 'completed' : 'declined';
        $riga->update(['outcome' => $nuovoOutcome]);
        $this->commessa->load('righe');
    }

    public function updatedInGaranzia(): void
    {
        if (! $this->inGaranzia) {
            $this->garanziaId    = null;
            $this->casaMadreId   = null;
        }
    }

    public function render()
    {
        $righe = $this->commessa->righe()->with(['articolo', 'garanzia', 'casaMadre'])->orderBy('ordinamento')->get();

        $garanzieAttive = $this->commessa->veicolo
            ? Garanzia::attive()->perVeicolo($this->commessa->veicolo->id)->with('casaMadre')->get()
            : collect();

        $caseMadri     = CasaMadre::orderBy('ragione_sociale')->get();
        $tariffeOrarie = TariffaOraria::attive()->orderByDesc('is_default')->orderBy('nome')->get();

        // Calcola prezzo suggerito per ogni riga articolo (per il badge matrice/manuale)
        $matriceService  = app(MatricePrezzoService::class);
        $prezziSuggeriti = $righe->mapWithKeys(function ($r) use ($matriceService) {
            if ($r->tipo->value === 'articolo' && $r->prezzo_acquisto) {
                return [$r->id => $matriceService->suggestPrice($r->prezzo_acquisto)];
            }
            return [$r->id => null];
        });

        return view('livewire.commesse.gestione-righe', [
            'righe'          => $righe,
            'tipiRiga'       => TipoRiga::cases(),
            'garanzieAttive' => $garanzieAttive,
            'caseMadri'      => $caseMadri,
            'tariffeOrarie'  => $tariffeOrarie,
            'prezziSuggeriti' => $prezziSuggeriti,
        ]);
    }
}
