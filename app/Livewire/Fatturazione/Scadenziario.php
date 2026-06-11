<?php

namespace App\Livewire\Fatturazione;

use App\Enums\MetodoPagamento;
use App\Enums\MetodoPagamentoFornitore;
use App\Enums\StatoDocumento;
use App\Enums\StatoFatturaAcquisto;
use App\Models\Documento;
use App\Models\FatturaAcquisto;
use App\Models\PagamentoFornitore;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Scadenziario extends Component
{
    use WithPagination;

    public string $tab         = 'clienti';
    public string $filtroStato = '';
    public string $dataDa      = '';
    public string $dataA       = '';

    // Modal pagamento clienti
    public bool $showPagamentoModal = false;
    public ?int $pagDocumentoId     = null;

    #[Rule('required|date')]
    public string $pagDataPagamento = '';

    #[Rule('required|numeric|min:0.01')]
    public float $pagImporto = 0;

    #[Rule('required|in:contanti,bonifico,carta,assegno,rid')]
    public string $pagMetodo = 'contanti';

    #[Rule('nullable|string|max:255')]
    public string $pagRiferimento = '';

    // Modal pagamento fornitori
    public bool $showPagamentoFornitoreModal = false;
    public ?int $pagFatturaId               = null;

    public string $pagFornitoreData       = '';
    public float  $pagFornitoreImporto    = 0;
    public string $pagFornitoreMetodo     = 'bonifico';
    public string $pagFornitoreRiferimento = '';

    public function mount(): void
    {
        $this->pagDataPagamento  = now()->toDateString();
        $this->pagFornitoreData  = now()->toDateString();
    }

    public function updatedTab(): void { $this->resetPage(); $this->filtroStato = ''; }

    public function apriPagamento(int $documentoId): void
    {
        $documento = Documento::findOrFail($documentoId);
        $this->authorize('registraPagamento', $documento);

        $this->pagDocumentoId   = $documentoId;
        $this->pagImporto       = (float) $documento->saldo;
        $this->showPagamentoModal = true;
    }

    public function registraPagamento(): void
    {
        $documento = Documento::with('pagamenti')->findOrFail($this->pagDocumentoId);
        $this->authorize('registraPagamento', $documento);

        $this->validate([
            'pagDataPagamento' => 'required|date',
            'pagImporto'       => 'required|numeric|min:0.01',
            'pagMetodo'        => 'required|in:contanti,bonifico,carta,assegno,rid',
            'pagRiferimento'   => 'nullable|string|max:255',
        ]);

        $documento->pagamenti()->create([
            'data_pagamento' => $this->pagDataPagamento,
            'importo'        => $this->pagImporto,
            'metodo'         => $this->pagMetodo,
            'riferimento'    => $this->pagRiferimento ?: null,
            'user_id'        => auth()->id(),
        ]);

        $documento->load('pagamenti');
        if ($documento->totale_pagato >= (float) $documento->totale) {
            $documento->update(['stato' => StatoDocumento::Pagata]);
        }

        $this->showPagamentoModal = false;
        $this->pagDocumentoId     = null;
        session()->flash('success', 'Pagamento registrato.');
    }

    public function apriPagamentoFornitore(int $fatturaId): void
    {
        $fat = FatturaAcquisto::with('pagamenti')->findOrFail($fatturaId);
        $this->pagFatturaId             = $fatturaId;
        $this->pagFornitoreImporto      = $fat->saldo;
        $this->pagFornitoreData         = now()->toDateString();
        $this->pagFornitoreMetodo       = 'bonifico';
        $this->pagFornitoreRiferimento  = '';
        $this->showPagamentoFornitoreModal = true;
    }

    public function registraPagamentoFornitore(): void
    {
        $this->validate([
            'pagFornitoreData'      => 'required|date',
            'pagFornitoreImporto'   => 'required|numeric|min:0.01',
            'pagFornitoreMetodo'    => 'required|in:contanti,bonifico,carta,assegno,rid,riba',
            'pagFornitoreRiferimento' => 'nullable|string|max:255',
        ]);

        $fat = FatturaAcquisto::with('pagamenti')->findOrFail($this->pagFatturaId);

        PagamentoFornitore::create([
            'fattura_acquisto_id' => $fat->id,
            'data_pagamento'      => $this->pagFornitoreData,
            'importo'             => $this->pagFornitoreImporto,
            'metodo'              => $this->pagFornitoreMetodo,
            'riferimento'         => $this->pagFornitoreRiferimento ?: null,
            'user_id'             => auth()->id(),
        ]);

        $fat->load('pagamenti');
        if ($fat->totale_pagato >= (float) $fat->totale) {
            $fat->update(['stato' => StatoFatturaAcquisto::Pagata]);
        }

        $this->showPagamentoFornitoreModal = false;
        $this->pagFatturaId = null;
        session()->flash('success', 'Pagamento fornitore registrato.');
    }

    public function render()
    {
        if ($this->tab === 'fornitori') {
            $statiAperti = [
                StatoFatturaAcquisto::Registrata->value,
            ];

            $query = FatturaAcquisto::with(['fornitore', 'pagamenti'])
                ->whereIn('stato', $statiAperti)
                ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
                ->when($this->dataDa, fn($q) => $q->whereDate('data_scadenza', '>=', $this->dataDa))
                ->when($this->dataA,  fn($q) => $q->whereDate('data_scadenza', '<=', $this->dataA))
                ->orderBy('data_scadenza');

            return view('livewire.fatturazione.scadenziario', [
                'tab'                => 'fornitori',
                'fatture'            => $query->paginate(20),
                'documenti'          => null,
                'totaleInsoluto'     => 0,
                'totaleDovuto'       => FatturaAcquisto::whereIn('stato', $statiAperti)->sum('totale'),
                'stati'              => StatoFatturaAcquisto::cases(),
                'metodiPagamento'    => MetodoPagamento::cases(),
                'metodiFornitore'    => MetodoPagamentoFornitore::cases(),
            ]);
        }

        // Tab clienti (default)
        $statiAperti = [
            StatoDocumento::Emessa->value,
            StatoDocumento::InviataSdi->value,
            StatoDocumento::AccettataSdi->value,
            StatoDocumento::ScartatasSdi->value,
        ];

        $query = Documento::with('cliente')
            ->whereIn('stato', $statiAperti)
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->dataDa,      fn($q) => $q->whereDate('data_scadenza', '>=', $this->dataDa))
            ->when($this->dataA,       fn($q) => $q->whereDate('data_scadenza', '<=', $this->dataA))
            ->orderBy('data_scadenza');

        $totaleInsoluto = (clone $query)->sum('totale')
            - Documento::whereIn('stato', $statiAperti)->join('pagamenti', 'documenti.id', '=', 'pagamenti.documento_id')->sum('pagamenti.importo');

        return view('livewire.fatturazione.scadenziario', [
            'tab'             => 'clienti',
            'documenti'       => $query->paginate(20),
            'fatture'         => null,
            'totaleInsoluto'  => $totaleInsoluto,
            'totaleDovuto'    => 0,
            'stati'           => array_filter(StatoDocumento::cases(), fn($s) => in_array($s->value, $statiAperti)),
            'metodiPagamento' => MetodoPagamento::cases(),
            'metodiFornitore' => MetodoPagamentoFornitore::cases(),
        ]);
    }
}
