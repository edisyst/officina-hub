<?php

namespace App\Livewire\Magazzino;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Actions\Magazzino\RettificaInventarioAction;
use App\Actions\Parts\BulkReorderAction;
use App\Actions\Parts\BulkUpdateLocationAction;
use App\Enums\TipoMovimento;
use App\Enums\UnitaMisura;
use App\Livewire\Concerns\WithBulkSelection;
use App\Livewire\Concerns\WithSavedFilters;
use App\Models\Articolo;
use App\Models\CategoriaArticolo;
use App\Models\Fornitore;
use App\Traits\EmitsActionCompleted;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListaArticoli extends Component
{
    use WithPagination, WithBulkSelection, EmitsActionCompleted, WithSavedFilters;

    protected string $pageKey = 'magazzino.articoli';
    protected array $filterWhitelist = ['search', 'filtroCategoria', 'filtroFornitore', 'soloSottoScorta'];

    public string $search = '';
    public string $filtroCategoria = '';
    public string $filtroFornitore = '';
    public bool $soloSottoScorta = false;

    // Modal articolo (crea/modifica)
    public bool $showArticoloModal = false;
    public ?int $editingId = null;

    #[Rule('required|string|max:50')]
    public string $codice = '';

    #[Rule('nullable|string|max:100')]
    public string $codice_fornitore = '';

    #[Rule('required|string|max:255')]
    public string $descrizione = '';

    #[Rule('nullable|string')]
    public string $descrizione_estesa = '';

    #[Rule('nullable|integer|exists:categorie_articoli,id')]
    public ?int $categoria_articolo_id = null;

    #[Rule('nullable|integer|exists:fornitori,id')]
    public ?int $fornitore_id = null;

    #[Rule('required|in:pz,lt,kg,ml,gr,mt')]
    public string $unita_misura = 'pz';

    #[Rule('required|numeric|min:0')]
    public float $prezzo_acquisto = 0;

    #[Rule('required|numeric|min:0')]
    public float $prezzo_vendita = 0;

    #[Rule('required|numeric|min:0|max:100')]
    public float $iva_percentuale = 22;

    #[Rule('required|integer|min:0')]
    public int $scorta_minima = 0;

    #[Rule('nullable|integer|min:0')]
    public ?int $scorta_massima = null;

    #[Rule('nullable|string|max:100')]
    public string $ubicazione = '';

    #[Rule('nullable|string')]
    public string $note_articolo = '';

    public bool $attivo = true;

    // Modal carico/reso
    public bool $showCaricoModal = false;
    public ?int $caricoArticoloId = null;
    public string $caricoTipo = 'carico';

    #[Rule('required|integer|min:1')]
    public int $caricoQuantita = 1;

    #[Rule('nullable|numeric|min:0')]
    public ?float $caricoPrezzoUnitario = null;

    #[Rule('nullable|string|max:100')]
    public string $caricoDocumento = '';

    #[Rule('nullable|date')]
    public string $caricoDataDocumento = '';

    #[Rule('nullable|string')]
    public string $caricoNote = '';

    // Modal rettifica
    public bool $showRettificaModal = false;
    public ?int $rettificaArticoloId = null;

    #[Rule('required|integer|min:0')]
    public int $nuovaGiacenza = 0;

    #[Rule('required|string|min:5')]
    public string $rettificaNota = '';

    // Bulk modals
    public bool $showBulkUbicazioneModal = false;
    public string $bulkNuovaUbicazione = '';
    public bool $showBulkReport = false;
    public array $bulkReport = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatedFiltroCategoria(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatedFiltroFornitore(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatedSoloSottoScorta(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    // --- WithBulkSelection ---

    protected function getBulkQuery(): Builder
    {
        return Articolo::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroCategoria, fn($q) => $q->where('categoria_articolo_id', $this->filtroCategoria))
            ->when($this->filtroFornitore, fn($q) => $q->where('fornitore_id', $this->filtroFornitore))
            ->when($this->soloSottoScorta, fn($q) => $q->sottoScorta());
    }

    protected function getPageIds(): array
    {
        return $this->getBulkQuery()
            ->orderBy('descrizione')
            ->paginate(25)
            ->pluck('id')
            ->toArray();
    }

    protected function authorizeBulk(string $action): void
    {
        $this->authorize('create', Articolo::class);
    }

    // --- Bulk actions ---

    public function bulkRiordina(): void
    {
        $this->authorizeBulk('update');
        $ids = $this->resolveIds();

        if (empty($ids)) {
            session()->flash('error', 'Nessun articolo selezionato.');
            return;
        }

        $result = app(BulkReorderAction::class)->execute($ids, auth()->user());

        $this->bulkReport     = $result;
        $this->showBulkReport = true;
        $this->deselectAll();
    }

    public function mount(): void
    {
        $this->initSavedFilters();
    }

    public function apriBulkUbicazioneModal(): void
    {
        $this->authorizeBulk('update');
        $this->bulkNuovaUbicazione = '';
        $this->showBulkUbicazioneModal = true;
    }

    public function eseguiBulkUbicazione(): void
    {
        $this->authorizeBulk('update');
        $this->validate(['bulkNuovaUbicazione' => 'required|string|max:100'], [], ['bulkNuovaUbicazione' => 'ubicazione']);

        $ids        = $this->resolveIds();
        $aggiornati = app(BulkUpdateLocationAction::class)->execute($ids, $this->bulkNuovaUbicazione, auth()->user());

        session()->flash('success', "{$aggiornati} articoli aggiornati.");
        $this->showBulkUbicazioneModal = false;
        $this->deselectAll();
    }

    public function exportCsv(): StreamedResponse
    {
        $ids = $this->resolveIds();

        $articoli = Articolo::with(['categoria', 'fornitore'])
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->orderBy('descrizione')
            ->get();

        return response()->streamDownload(function () use ($articoli) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Codice', 'Descrizione', 'Categoria', 'Fornitore', 'U.M.', 'Giacenza', 'Scorta Min.', 'Prezzo Acq.', 'Prezzo Vend.', 'Ubicazione'], ';');

            foreach ($articoli as $art) {
                fputcsv($handle, [
                    $art->codice,
                    $art->descrizione,
                    $art->categoria?->nome ?? '',
                    $art->fornitore?->ragione_sociale ?? '',
                    $art->unita_misura->label(),
                    $art->giacenza_attuale,
                    $art->scorta_minima,
                    number_format((float) $art->prezzo_acquisto, 2, ',', '.'),
                    number_format((float) $art->prezzo_vendita, 2, ',', '.'),
                    $art->ubicazione ?? '',
                ], ';');
            }

            fclose($handle);
        }, 'articoli-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // --- Inline edit ---

    /**
     * Saves an inline-edited field on an Articolo.
     * Returns true on success (Alpine uses this to update orig), false on failure.
     */
    public function salvaInlineEdit(int $id, string $field, mixed $value): bool
    {
        $art = Articolo::findOrFail($id);
        $this->authorize('update', $art);

        $allowed = [
            'ubicazione'    => 'nullable|string|max:100',
            'prezzo_vendita' => 'required|numeric|min:0',
            'scorta_minima' => 'required|integer|min:0',
        ];

        if (! array_key_exists($field, $allowed)) {
            return false;
        }

        try {
            $validated = validator([$field => $value], [$field => $allowed[$field]])->validate();
        } catch (\Illuminate\Validation\ValidationException) {
            return false;
        }

        $old = $art->$field;
        $art->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($art)
            ->withProperties(['old' => [$field => $old], 'new' => [$field => $validated[$field]]])
            ->log('modifica_inline');

        return true;
    }

    // --- Existing methods unchanged ---

    public function apriArticoloModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->resetValidation();

        if ($id) {
            $a = Articolo::findOrFail($id);
            $this->fill([
                'codice'               => $a->codice,
                'codice_fornitore'     => $a->codice_fornitore ?? '',
                'descrizione'          => $a->descrizione,
                'descrizione_estesa'   => $a->descrizione_estesa ?? '',
                'categoria_articolo_id' => $a->categoria_articolo_id,
                'fornitore_id'         => $a->fornitore_id,
                'unita_misura'         => $a->unita_misura->value,
                'prezzo_acquisto'      => (float) $a->prezzo_acquisto,
                'prezzo_vendita'       => (float) $a->prezzo_vendita,
                'iva_percentuale'      => (float) $a->iva_percentuale,
                'scorta_minima'        => $a->scorta_minima,
                'scorta_massima'       => $a->scorta_massima,
                'ubicazione'           => $a->ubicazione ?? '',
                'note_articolo'        => $a->note ?? '',
                'attivo'               => $a->attivo,
            ]);
        } else {
            $this->reset(['codice', 'codice_fornitore', 'descrizione', 'descrizione_estesa', 'categoria_articolo_id', 'fornitore_id', 'ubicazione', 'note_articolo']);
            $this->unita_misura = 'pz';
            $this->prezzo_acquisto = 0;
            $this->prezzo_vendita = 0;
            $this->iva_percentuale = 22;
            $this->scorta_minima = 0;
            $this->scorta_massima = null;
            $this->attivo = true;
        }

        $this->showArticoloModal = true;
    }

    public function salvaArticolo(): void
    {
        $this->validate([
            'codice'               => ($this->editingId ? 'required|string|max:50|unique:articoli,codice,' . $this->editingId : 'required|string|max:50|unique:articoli,codice'),
            'codice_fornitore'     => 'nullable|string|max:100',
            'descrizione'          => 'required|string|max:255',
            'descrizione_estesa'   => 'nullable|string',
            'categoria_articolo_id' => 'nullable|integer|exists:categorie_articoli,id',
            'fornitore_id'         => 'nullable|integer|exists:fornitori,id',
            'unita_misura'         => 'required|in:pz,lt,kg,ml,gr,mt',
            'prezzo_acquisto'      => 'required|numeric|min:0',
            'prezzo_vendita'       => 'required|numeric|min:0',
            'iva_percentuale'      => 'required|numeric|min:0|max:100',
            'scorta_minima'        => 'required|integer|min:0',
            'scorta_massima'       => 'nullable|integer|min:0',
            'ubicazione'           => 'nullable|string|max:100',
            'note_articolo'        => 'nullable|string',
        ]);

        $this->authorize('create', Articolo::class);

        $dati = [
            'codice'               => $this->codice,
            'codice_fornitore'     => $this->codice_fornitore ?: null,
            'descrizione'          => $this->descrizione,
            'descrizione_estesa'   => $this->descrizione_estesa ?: null,
            'categoria_articolo_id' => $this->categoria_articolo_id,
            'fornitore_id'         => $this->fornitore_id,
            'unita_misura'         => $this->unita_misura,
            'prezzo_acquisto'      => $this->prezzo_acquisto,
            'prezzo_vendita'       => $this->prezzo_vendita,
            'iva_percentuale'      => $this->iva_percentuale,
            'scorta_minima'        => $this->scorta_minima,
            'scorta_massima'       => $this->scorta_massima,
            'ubicazione'           => $this->ubicazione ?: null,
            'attivo'               => $this->attivo,
            'note'                 => $this->note_articolo ?: null,
        ];

        if ($this->editingId) {
            Articolo::findOrFail($this->editingId)->update($dati);
            session()->flash('success', 'Articolo aggiornato.');
        } else {
            Articolo::create($dati);
            session()->flash('success', 'Articolo creato.');
        }

        $this->showArticoloModal = false;
    }

    public function eliminaArticolo(int $id): void
    {
        $articolo = Articolo::withCount('movimenti')->findOrFail($id);
        $this->authorize('delete', $articolo);

        if ($articolo->movimenti_count > 0) {
            $articolo->update(['attivo' => false]);
            session()->flash('success', 'Articolo disattivato (ha movimenti associati, non può essere eliminato).');
            return;
        }

        $articolo->delete();
        session()->flash('success', 'Articolo eliminato.');
    }

    public function apriCaricoModal(int $id, string $tipo = 'carico'): void
    {
        $this->caricoArticoloId = $id;
        $this->caricoTipo = $tipo;
        $this->resetValidation();
        $this->reset(['caricoQuantita', 'caricoPrezzoUnitario', 'caricoDocumento', 'caricoDataDocumento', 'caricoNote']);
        $this->caricoQuantita = 1;
        $this->showCaricoModal = true;
    }

    public function eseguiCarico(): void
    {
        $this->validate([
            'caricoQuantita'      => 'required|integer|min:1',
            'caricoPrezzoUnitario' => 'nullable|numeric|min:0',
            'caricoDocumento'     => 'nullable|string|max:100',
            'caricoDataDocumento' => 'nullable|date',
            'caricoNote'          => 'nullable|string',
        ]);

        $articolo = Articolo::findOrFail($this->caricoArticoloId);
        $this->authorize('movimenta', $articolo);

        $movimento = app(CaricoManualeAction::class)->execute(
            articolo: $articolo,
            tipo: TipoMovimento::from($this->caricoTipo),
            quantita: $this->caricoQuantita,
            utente: auth()->user(),
            prezzoUnitario: $this->caricoPrezzoUnitario,
            documentoFornitore: $this->caricoDocumento ?: null,
            dataDocumento: $this->caricoDataDocumento ? new \DateTime($this->caricoDataDocumento) : null,
            note: $this->caricoNote ?: null,
        );

        $tipo = TipoMovimento::from($this->caricoTipo);
        $activityId = $this->markLastActivityUndoable($movimento);
        $this->emitActionCompleted("{$tipo->label()}: {$this->caricoQuantita}× {$articolo->descrizione}", $activityId);
        session()->flash('success', 'Movimento registrato. Giacenza aggiornata.');
        $this->showCaricoModal = false;
    }

    public function apriRettificaModal(int $id): void
    {
        $this->rettificaArticoloId = $id;
        $articolo = Articolo::findOrFail($id);
        $this->nuovaGiacenza = $articolo->giacenza_attuale;
        $this->rettificaNota = '';
        $this->resetValidation();
        $this->showRettificaModal = true;
    }

    public function eseguiRettifica(): void
    {
        $this->validate([
            'nuovaGiacenza' => 'required|integer|min:0',
            'rettificaNota' => 'required|string|min:5',
        ]);

        $articolo = Articolo::findOrFail($this->rettificaArticoloId);
        $this->authorize('movimenta', $articolo);

        app(RettificaInventarioAction::class)->execute(
            articolo: $articolo,
            nuovaGiacenza: $this->nuovaGiacenza,
            utente: auth()->user(),
            nota: $this->rettificaNota,
        );

        session()->flash('success', 'Rettifica inventario registrata.');
        $this->showRettificaModal = false;
    }

    public function render()
    {
        $articoli = $this->getBulkQuery()
            ->with(['categoria', 'fornitore'])
            ->orderBy('descrizione')
            ->paginate(25);

        $categorie   = CategoriaArticolo::orderBy('nome')->get();
        $fornitori   = Fornitore::orderBy('ragione_sociale')->get();
        $unitaMisura = UnitaMisura::cases();
        $tipiCarico  = [
            TipoMovimento::Carico,
            TipoMovimento::ResoFornitore,
            TipoMovimento::ResoCliente,
        ];

        return view('livewire.magazzino.lista-articoli', compact(
            'articoli', 'categorie', 'fornitori', 'unitaMisura', 'tipiCarico'
        ) + ['selectionCount' => $this->selectionCount()]);
    }
}
