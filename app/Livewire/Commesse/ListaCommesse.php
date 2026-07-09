<?php

namespace App\Livewire\Commesse;

use App\Actions\WorkOrders\BulkChangeWorkOrderStatusAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Livewire\Concerns\WithBulkSelection;
use App\Models\Commessa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListaCommesse extends Component
{
    use WithPagination, WithBulkSelection;

    public string $search = '';
    public string $filtroStato = '';
    public string $filtroTipo = '';
    public string $vista = 'tabella';

    // Bulk stato modale
    public bool $showBulkStatoModal = false;
    public string $bulkStatoTarget = '';
    public string $bulkNota = '';
    public array $bulkReport = [];

    // Result modale
    public bool $showBulkReport = false;

    public function mount(): void
    {
        if (session('commesse_vista') === 'board') {
            $this->redirect(route('commesse.board'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatingFiltroStato(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
        $this->deselectAll();
    }

    public function updatingVista(): void
    {
        session(['commesse_vista' => 'tabella']);
    }

    // --- WithBulkSelection implementation ---

    protected function getBulkQuery(): Builder
    {
        return Commessa::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->filtroTipo, fn($q) => $q->where('tipo', $this->filtroTipo));
    }

    protected function getPageIds(): array
    {
        return $this->getBulkQuery()
            ->latest('data_ingresso')
            ->paginate(20)
            ->pluck('id')
            ->toArray();
    }

    protected function authorizeBulk(string $action): void
    {
        $this->authorize('create', Commessa::class);
    }

    // --- Bulk actions ---

    public function apriBulkStatoModal(): void
    {
        $this->authorizeBulk('update');
        $this->bulkStatoTarget = '';
        $this->bulkNota = '';
        $this->showBulkStatoModal = true;
    }

    public function eseguiBulkCambioStato(): void
    {
        $this->authorizeBulk('update');

        if (! $this->bulkStatoTarget) {
            $this->addError('bulkStatoTarget', 'Seleziona uno stato target.');
            return;
        }

        $nuovoStato = StatoCommessa::from($this->bulkStatoTarget);
        $ids        = $this->resolveIds();

        /** @var BulkChangeWorkOrderStatusAction $action */
        $action = app(BulkChangeWorkOrderStatusAction::class);
        $report = $action->execute($ids, $nuovoStato, auth()->user(), $this->bulkNota ?: null);

        $this->bulkReport          = $report;
        $this->showBulkStatoModal  = false;
        $this->showBulkReport      = true;
        $this->deselectAll();
    }

    public function stampaMassiva(): void
    {
        $ids = $this->resolveIds();

        if (empty($ids)) {
            session()->flash('error', 'Nessuna commessa selezionata.');
            return;
        }

        $this->redirect(route('commesse.stampa-massiva', ['ids' => implode(',', $ids)]));
    }

    public function exportCsv(): StreamedResponse
    {
        $ids = $this->resolveIds();

        $commesse = Commessa::with(['cliente', 'veicolo'])
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->latest('data_ingresso')
            ->get();

        return response()->streamDownload(function () use ($commesse) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Numero', 'Cliente', 'Veicolo', 'Targa', 'Tipo', 'Stato', 'Ingresso', 'Uscita Prevista'], ';');

            foreach ($commesse as $c) {
                fputcsv($handle, [
                    $c->numero,
                    $c->cliente->nome_completo,
                    trim(($c->veicolo->marca ?? '') . ' ' . ($c->veicolo->modello ?? '')),
                    $c->veicolo->targa ?? '',
                    $c->tipo->label(),
                    $c->stato->label(),
                    $c->data_ingresso->format('d/m/Y'),
                    $c->data_uscita_prevista?->format('d/m/Y') ?? '',
                ], ';');
            }

            fclose($handle);
        }, 'commesse-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $query = $this->getBulkQuery()->with(['cliente', 'veicolo'])->latest('data_ingresso');

        if ($this->vista === 'kanban') {
            $commessePerStato = [];
            foreach (StatoCommessa::cases() as $stato) {
                $commessePerStato[$stato->value] = (clone $query)
                    ->where('stato', $stato->value)
                    ->limit(20)
                    ->get();
            }
            $commesse = collect();
        } else {
            $commesse         = $query->paginate(20);
            $commessePerStato = [];
        }

        return view('livewire.commesse.lista-commesse', [
            'commesse'         => $commesse,
            'commessePerStato' => $commessePerStato,
            'stati'            => StatoCommessa::cases(),
            'tipi'             => TipoCommessa::cases(),
            'selectionCount'   => $this->selectionCount(),
        ]);
    }
}
