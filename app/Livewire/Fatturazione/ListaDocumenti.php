<?php

namespace App\Livewire\Fatturazione;

use App\Actions\Fatturazione\GeneraFatturaAction;
use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\TipoDocumento;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Documento;
use Livewire\Component;
use Livewire\WithPagination;

class ListaDocumenti extends Component
{
    use WithPagination;

    public string $search   = '';
    public string $filtroTipo  = '';
    public string $filtroStato = '';
    public string $filtroAnno  = '';

    // Modal genera fattura da commessa
    public bool $showGeneraModal = false;
    public string $commessaSearch = '';
    public ?int $commessaSelezionataId = null;

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedFiltroTipo(): void  { $this->resetPage(); }
    public function updatedFiltroStato(): void { $this->resetPage(); }
    public function updatedFiltroAnno(): void  { $this->resetPage(); }

    /** Genera la fattura per la commessa selezionata */
    public function generaFattura(): void
    {
        $this->authorize('create', Documento::class);

        if (!$this->commessaSelezionataId) {
            $this->addError('commessaSelezionataId', 'Seleziona una commessa.');
            return;
        }

        $commessa = Commessa::with('righe')->findOrFail($this->commessaSelezionataId);

        if (!in_array($commessa->stato, [StatoCommessa::Completata, StatoCommessa::Consegnata])) {
            $this->addError('commessaSelezionataId', 'La commessa deve essere in stato Completata o Consegnata.');
            return;
        }

        $documento = app(GeneraFatturaAction::class)->execute($commessa);

        $this->showGeneraModal         = false;
        $this->commessaSelezionataId   = null;
        $this->commessaSearch          = '';

        session()->flash('success', "Fattura {$documento->numero} creata in bozza.");
        $this->redirectRoute('fatturazione.documenti.show', $documento->id);
    }

    /** Export CSV dei documenti filtrati */
    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = $this->buildQuery();

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Numero', 'Tipo', 'Data', 'Cliente', 'Imponibile', 'IVA', 'Totale', 'Stato'], ';');

            foreach ($query->cursor() as $doc) {
                fputcsv($handle, [
                    $doc->numero,
                    $doc->tipo->label(),
                    $doc->data_emissione->format('d/m/Y'),
                    $doc->cliente->nome_completo,
                    number_format((float) $doc->imponibile, 2, ',', '.'),
                    number_format((float) $doc->iva_totale, 2, ',', '.'),
                    number_format((float) $doc->totale, 2, ',', '.'),
                    $doc->stato->label(),
                ], ';');
            }

            fclose($handle);
        }, 'documenti-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery()
    {
        $query = Documento::with('cliente')
            ->when($this->filtroTipo,  fn($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->filtroAnno,  fn($q) => $q->where('anno', $this->filtroAnno))
            ->when($this->search, fn($q) => $q->where(function ($sq) {
                $sq->where('numero', 'like', "%{$this->search}%")
                   ->orWhereHas('cliente', fn($c) => $c->search($this->search));
            }))
            ->orderByDesc('data_emissione')
            ->orderByDesc('progressivo');

        return $query;
    }

    public function render()
    {
        $commesseDisponibili = collect();
        if ($this->showGeneraModal && strlen($this->commessaSearch) >= 2) {
            $commesseDisponibili = Commessa::with('cliente')
                ->whereIn('stato', [StatoCommessa::Completata->value, StatoCommessa::Consegnata->value])
                ->search($this->commessaSearch)
                ->limit(10)
                ->get();
        }

        return view('livewire.fatturazione.lista-documenti', [
            'documenti'          => $this->buildQuery()->paginate(20),
            'tipi'               => TipoDocumento::cases(),
            'stati'              => StatoDocumento::cases(),
            'anni'               => Documento::distinct()->orderByDesc('anno')->pluck('anno'),
            'commesseDisponibili' => $commesseDisponibili,
        ]);
    }
}
