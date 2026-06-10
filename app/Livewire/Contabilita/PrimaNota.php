<?php

namespace App\Livewire\Contabilita;

use App\Enums\ContoPrimaNota;
use App\Enums\MetodoPrimaNota;
use App\Enums\TipoPrimaNota;
use App\Models\PrimaNota as PrimaNotaModel;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PrimaNota extends Component
{
    use WithPagination;

    public string $filtroDal    = '';
    public string $filtroAl     = '';
    public string $filtroTipo   = '';
    public string $filtroMetodo = '';
    public string $filtroConto  = '';

    public bool $showModal   = false;
    public ?int $editingId   = null;

    #[Rule('required|date')]
    public string $data = '';

    #[Rule('required|string|max:255')]
    public string $causale = '';

    #[Rule('required|in:entrata,uscita')]
    public string $tipo = 'entrata';

    #[Rule('required|numeric|min:0.01')]
    public string $importo = '';

    #[Rule('required|in:contanti,bonifico,carta,assegno,rid,altro')]
    public string $metodo = 'contanti';

    #[Rule('required|in:cassa,banca,pos')]
    public string $conto = 'cassa';

    public string $note = '';

    public function mount(): void
    {
        $this->filtroDal = now()->startOfMonth()->format('Y-m-d');
        $this->filtroAl  = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedFiltroDal(): void { $this->resetPage(); }
    public function updatedFiltroAl(): void  { $this->resetPage(); }
    public function updatedFiltroTipo(): void { $this->resetPage(); }
    public function updatedFiltroMetodo(): void { $this->resetPage(); }
    public function updatedFiltroConto(): void { $this->resetPage(); }

    /** Aggiorna il conto suggerito in base al metodo selezionato */
    public function updatedMetodo(): void
    {
        $metodoEnum = MetodoPrimaNota::from($this->metodo);
        $this->conto = ContoPrimaNota::daMetodo($metodoEnum)->value;
    }

    public function apriModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->data      = now()->format('Y-m-d');
        $this->causale   = '';
        $this->tipo      = 'entrata';
        $this->importo   = '';
        $this->metodo    = 'contanti';
        $this->conto     = 'cassa';
        $this->note      = '';
        $this->showModal = true;
    }

    public function modificaMovimento(int $id): void
    {
        $movimento = PrimaNotaModel::findOrFail($id);

        if ($movimento->automatico) {
            session()->flash('error', 'I movimenti automatici non possono essere modificati.');
            return;
        }

        $this->editingId = $id;
        $this->data      = $movimento->data->format('Y-m-d');
        $this->causale   = $movimento->causale;
        $this->tipo      = $movimento->tipo->value;
        $this->importo   = (string) $movimento->importo;
        $this->metodo    = $movimento->metodo->value;
        $this->conto     = $movimento->conto->value;
        $this->note      = $movimento->note ?? '';
        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();
        $this->checkRuolo();

        $dati = [
            'data'      => $this->data,
            'causale'   => $this->causale,
            'tipo'      => $this->tipo,
            'importo'   => $this->importo,
            'metodo'    => $this->metodo,
            'conto'     => $this->conto,
            'note'      => $this->note ?: null,
            'automatico' => false,
            'user_id'   => auth()->id(),
        ];

        if ($this->editingId) {
            $movimento = PrimaNotaModel::findOrFail($this->editingId);
            if ($movimento->automatico) {
                session()->flash('error', 'Operazione non consentita.');
                return;
            }
            $movimento->update($dati);
            session()->flash('success', 'Movimento aggiornato.');
        } else {
            PrimaNotaModel::create($dati);
            session()->flash('success', 'Movimento aggiunto.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function elimina(int $id): void
    {
        $movimento = PrimaNotaModel::findOrFail($id);

        if ($movimento->automatico) {
            session()->flash('error', 'I movimenti automatici non possono essere eliminati.');
            return;
        }

        $movimento->delete();
        session()->flash('success', 'Movimento eliminato.');
    }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = $this->buildQuery();

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Data', 'Causale', 'Tipo', 'Importo', 'Metodo', 'Conto', 'Note', 'Automatico'], ';');

            foreach ($query->cursor() as $m) {
                fputcsv($handle, [
                    $m->data->format('d/m/Y'),
                    $m->causale,
                    $m->tipo->label(),
                    number_format((float) $m->importo, 2, ',', '.'),
                    $m->metodo->label(),
                    $m->conto->label(),
                    $m->note ?? '',
                    $m->automatico ? 'Sì' : 'No',
                ], ';');
            }

            fclose($handle);
        }, 'prima-nota-' . now()->format('Y-m') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery()
    {
        return PrimaNotaModel::with(['documento.cliente', 'user'])
            ->when($this->filtroDal, fn($q) => $q->whereDate('data', '>=', $this->filtroDal))
            ->when($this->filtroAl,  fn($q) => $q->whereDate('data', '<=', $this->filtroAl))
            ->when($this->filtroTipo,   fn($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroMetodo, fn($q) => $q->where('metodo', $this->filtroMetodo))
            ->when($this->filtroConto,  fn($q) => $q->where('conto', $this->filtroConto))
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc');
    }

    private function checkRuolo(): void
    {
        // I movimenti manuali richiedono ruolo admin o cassa
        if (! auth()->user()->hasAnyRole(['admin', 'cassa'])) {
            abort(403);
        }
    }

    public function render()
    {
        $query      = $this->buildQuery();
        $movimenti  = $query->paginate(25);

        $totaliQuery = $this->buildQuery();
        $tutti = $totaliQuery->get();

        $totali = [
            'entrate' => $tutti->where('tipo', TipoPrimaNota::Entrata)->sum(fn($m) => (float) $m->importo),
            'uscite'  => $tutti->where('tipo', TipoPrimaNota::Uscita)->sum(fn($m) => (float) $m->importo),
        ];
        $totali['saldo'] = $totali['entrate'] - $totali['uscite'];

        return view('livewire.contabilita.prima-nota', [
            'movimenti'    => $movimenti,
            'totali'       => $totali,
            'tipi'         => TipoPrimaNota::cases(),
            'metodi'       => MetodoPrimaNota::cases(),
            'conti'        => ContoPrimaNota::cases(),
        ]);
    }
}
