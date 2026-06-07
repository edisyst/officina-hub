<?php

namespace App\Livewire\Fatturazione;

use App\Enums\MetodoPagamento;
use App\Enums\StatoDocumento;
use App\Models\Documento;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Scadenziario extends Component
{
    use WithPagination;

    public string $filtroStato = '';
    public string $dataDa     = '';
    public string $dataA      = '';

    // Modal pagamento rapido
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

    public function mount(): void
    {
        $this->pagDataPagamento = now()->toDateString();
    }

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

    public function render()
    {
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
            'documenti'       => $query->paginate(20),
            'totaleInsoluto'  => $totaleInsoluto,
            'stati'           => array_filter(StatoDocumento::cases(), fn($s) => in_array($s->value, $statiAperti)),
            'metodiPagamento' => MetodoPagamento::cases(),
        ]);
    }
}
