<?php

namespace App\Livewire\Acquisti;

use App\Enums\MetodoPagamentoFornitore;
use App\Enums\StatoFatturaAcquisto;
use App\Models\DdtFornitore;
use App\Models\FatturaAcquisto;
use App\Models\FatturaAcquistoRiga;
use App\Models\Fornitore;
use App\Models\PagamentoFornitore;
use App\Services\FatturaPAParser;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ListaFattureAcquisto extends Component
{
    use WithPagination, WithFileUploads;

    public string $search        = '';
    public string $filtroFornitore = '';
    public string $filtroStato   = '';
    public string $filtroDal     = '';
    public string $filtroAl      = '';

    // Modal registrazione manuale
    public bool $showModal       = false;
    public ?int $editingId       = null;

    #[Rule('required|exists:fornitori,id')]
    public int $fFornitoreId = 0;

    #[Rule('required|string|max:100')]
    public string $fNumero = '';

    #[Rule('required|date')]
    public string $fDataFattura = '';

    #[Rule('required|date')]
    public string $fDataRicezione = '';

    #[Rule('nullable|date')]
    public string $fDataScadenza = '';

    #[Rule('required|numeric|min:0')]
    public float $fImponibile = 0;

    #[Rule('required|numeric|min:0')]
    public float $fIvaTotale = 0;

    #[Rule('nullable|string|max:1000')]
    public string $fNote = '';

    // Righe fattura
    public array $fRighe = [];

    // Modal pagamento rapido
    public bool $showPagamentoModal  = false;
    public ?int $pagFatturaId        = null;

    #[Rule('required|date')]
    public string $pagData   = '';

    #[Rule('required|numeric|min:0.01')]
    public float $pagImporto = 0;

    #[Rule('required')]
    public string $pagMetodo = 'bonifico';

    #[Rule('nullable|string|max:255')]
    public string $pagRiferimento = '';

    // Modal DDT fine mese
    public bool $showDdtModal    = false;
    public int $ddtFornitoreId   = 0;
    public string $ddtDal        = '';
    public string $ddtAl         = '';
    public array $ddtSelezionati = [];
    public array $ddtDisponibili = [];

    // Upload XML
    public $xmlFile = null;

    public function mount(): void
    {
        $this->fDataFattura  = today()->toDateString();
        $this->fDataRicezione = today()->toDateString();
        $this->pagData       = today()->toDateString();
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFiltroFornitore(): void { $this->resetPage(); }
    public function updatedFiltroStato(): void { $this->resetPage(); }
    public function updatedFiltroDal(): void  { $this->resetPage(); }
    public function updatedFiltroAl(): void   { $this->resetPage(); }

    // ── Registrazione manuale ──────────────────────────────────────────────

    public function apriModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->editingId     = $id;
        $this->fRighe        = [];

        if ($id) {
            $fat = FatturaAcquisto::with('righe')->findOrFail($id);
            $this->fFornitoreId   = $fat->fornitore_id;
            $this->fNumero        = $fat->numero_fattura_fornitore;
            $this->fDataFattura   = $fat->data_fattura->toDateString();
            $this->fDataRicezione = $fat->data_ricezione->toDateString();
            $this->fDataScadenza  = $fat->data_scadenza?->toDateString() ?? '';
            $this->fImponibile    = (float) $fat->imponibile;
            $this->fIvaTotale     = (float) $fat->iva_totale;
            $this->fNote          = $fat->note ?? '';
            $this->fRighe         = $fat->righe->map(fn($r) => [
                'id'              => $r->id,
                'descrizione'     => $r->descrizione,
                'quantita'        => $r->quantita,
                'prezzo_unitario' => $r->prezzo_unitario,
                'iva_percentuale' => $r->iva_percentuale,
                'imponibile_riga' => $r->imponibile_riga,
            ])->toArray();
        } else {
            $this->fFornitoreId   = 0;
            $this->fNumero        = '';
            $this->fDataFattura   = today()->toDateString();
            $this->fDataRicezione = today()->toDateString();
            $this->fDataScadenza  = '';
            $this->fImponibile    = 0;
            $this->fIvaTotale     = 0;
            $this->fNote          = '';
            $this->aggiungiRigaFattura();
        }

        $this->showModal = true;
    }

    public function aggiungiRigaFattura(): void
    {
        $this->fRighe[] = [
            'id'              => null,
            'descrizione'     => '',
            'quantita'        => 1,
            'prezzo_unitario' => 0,
            'iva_percentuale' => 22,
            'imponibile_riga' => 0,
        ];
    }

    public function rimuoviRigaFattura(int $idx): void
    {
        array_splice($this->fRighe, $idx, 1);
        $this->ricalcolaTotali();
    }

    public function ricalcolaTotali(): void
    {
        $imponibile = 0;
        $iva        = 0;

        foreach ($this->fRighe as &$riga) {
            $imp = (float) $riga['quantita'] * (float) $riga['prezzo_unitario'];
            $riga['imponibile_riga'] = round($imp, 2);
            $imponibile += $imp;
            $iva        += $imp * ((float) $riga['iva_percentuale'] / 100);
        }
        unset($riga);

        $this->fImponibile = round($imponibile, 2);
        $this->fIvaTotale  = round($iva, 2);
    }

    public function salva(): void
    {
        $this->validate([
            'fFornitoreId'   => 'required|exists:fornitori,id',
            'fNumero'        => 'required|string|max:100',
            'fDataFattura'   => 'required|date',
            'fDataRicezione' => 'required|date',
            'fDataScadenza'  => 'nullable|date',
            'fImponibile'    => 'required|numeric|min:0',
            'fIvaTotale'     => 'required|numeric|min:0',
        ]);

        $dati = [
            'fornitore_id'            => $this->fFornitoreId,
            'numero_fattura_fornitore' => $this->fNumero,
            'data_fattura'            => $this->fDataFattura,
            'data_ricezione'          => $this->fDataRicezione,
            'data_scadenza'           => $this->fDataScadenza ?: null,
            'imponibile'              => $this->fImponibile,
            'iva_totale'              => $this->fIvaTotale,
            'totale'                  => $this->fImponibile + $this->fIvaTotale,
            'note'                    => $this->fNote ?: null,
            'user_id'                 => auth()->id(),
        ];

        if ($this->editingId) {
            $fat = FatturaAcquisto::findOrFail($this->editingId);
            $fat->update($dati);
            $fat->righe()->delete();
        } else {
            $dati['stato'] = StatoFatturaAcquisto::Ricevuta;
            $fat = FatturaAcquisto::create($dati);
        }

        foreach ($this->fRighe as $riga) {
            if (! $riga['descrizione']) continue;
            $fat->righe()->create([
                'descrizione'     => $riga['descrizione'],
                'quantita'        => $riga['quantita'],
                'prezzo_unitario' => $riga['prezzo_unitario'],
                'iva_percentuale' => $riga['iva_percentuale'],
                'imponibile_riga' => $riga['imponibile_riga'],
            ]);
        }

        $this->showModal = false;
        session()->flash('success', 'Fattura salvata.');
    }

    public function registra(int $id): void
    {
        $fat = FatturaAcquisto::findOrFail($id);
        $fat->update(['stato' => StatoFatturaAcquisto::Registrata]);
        session()->flash('success', 'Fattura registrata.');
    }

    public function elimina(int $id): void
    {
        FatturaAcquisto::findOrFail($id)->delete();
        session()->flash('success', 'Fattura eliminata.');
    }

    // ── Pagamento ─────────────────────────────────────────────────────────

    public function apriPagamento(int $fatturaId): void
    {
        $fat = FatturaAcquisto::with('pagamenti')->findOrFail($fatturaId);
        $this->pagFatturaId  = $fatturaId;
        $this->pagImporto    = $fat->saldo;
        $this->pagData       = today()->toDateString();
        $this->pagMetodo     = 'bonifico';
        $this->pagRiferimento = '';
        $this->showPagamentoModal = true;
    }

    public function registraPagamento(): void
    {
        $this->validate([
            'pagData'       => 'required|date',
            'pagImporto'    => 'required|numeric|min:0.01',
            'pagMetodo'     => 'required|in:contanti,bonifico,carta,assegno,rid,riba',
            'pagRiferimento' => 'nullable|string|max:255',
        ]);

        $fat = FatturaAcquisto::with('pagamenti')->findOrFail($this->pagFatturaId);

        PagamentoFornitore::create([
            'fattura_acquisto_id' => $fat->id,
            'data_pagamento'      => $this->pagData,
            'importo'             => $this->pagImporto,
            'metodo'              => $this->pagMetodo,
            'riferimento'         => $this->pagRiferimento ?: null,
            'user_id'             => auth()->id(),
        ]);

        $fat->load('pagamenti');
        if ($fat->totale_pagato >= (float) $fat->totale) {
            $fat->update(['stato' => StatoFatturaAcquisto::Pagata]);
        }

        $this->showPagamentoModal = false;
        session()->flash('success', 'Pagamento registrato.');
    }

    // ── Fattura da DDT ────────────────────────────────────────────────────

    public function apriDdtModal(): void
    {
        $this->ddtFornitoreId  = 0;
        $this->ddtDal          = today()->startOfMonth()->toDateString();
        $this->ddtAl           = today()->toDateString();
        $this->ddtSelezionati  = [];
        $this->ddtDisponibili  = [];
        $this->showDdtModal    = true;
    }

    public function cercaDdt(): void
    {
        if (! $this->ddtFornitoreId) {
            $this->ddtDisponibili = [];
            return;
        }

        $this->ddtDisponibili = DdtFornitore::with('righe')
            ->where('fornitore_id', $this->ddtFornitoreId)
            ->whereDoesntHave('fatturaAcquisto')
            ->when($this->ddtDal, fn($q) => $q->whereDate('data_ddt', '>=', $this->ddtDal))
            ->when($this->ddtAl,  fn($q) => $q->whereDate('data_ddt', '<=', $this->ddtAl))
            ->orderBy('data_ddt')
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'numero_ddt' => $d->numero_ddt,
                'data_ddt'   => $d->data_ddt->format('d/m/Y'),
                'righe'      => $d->righe->count(),
            ])
            ->toArray();

        $this->ddtSelezionati = array_column($this->ddtDisponibili, 'id');
    }

    public function creaFatturaDaDdt(): void
    {
        if (empty($this->ddtSelezionati) || ! $this->ddtFornitoreId) {
            return;
        }

        $ddtList = DdtFornitore::with('righe.articolo')
            ->whereIn('id', $this->ddtSelezionati)
            ->where('fornitore_id', $this->ddtFornitoreId)
            ->get();

        $imponibile = 0.0;
        $iva        = 0.0;
        $righe      = [];

        foreach ($ddtList as $ddt) {
            foreach ($ddt->righe as $riga) {
                $imp = (float) $riga->quantita_ricevuta * (float) $riga->prezzo_unitario;
                $ivaPerc = 22.0;
                if ($riga->articolo) {
                    $ivaPerc = (float) $riga->articolo->iva_percentuale;
                }
                $righe[] = [
                    'articolo_id'     => $riga->articolo_id,
                    'descrizione'     => $riga->descrizione . " (DDT {$ddt->numero_ddt})",
                    'quantita'        => $riga->quantita_ricevuta,
                    'prezzo_unitario' => $riga->prezzo_unitario,
                    'iva_percentuale' => $ivaPerc,
                    'imponibile_riga' => round($imp, 2),
                ];
                $imponibile += $imp;
                $iva        += $imp * ($ivaPerc / 100);
            }
        }

        $fat = FatturaAcquisto::create([
            'fornitore_id'            => $this->ddtFornitoreId,
            'numero_fattura_fornitore' => 'DA-DEFINIRE',
            'data_fattura'            => today(),
            'data_ricezione'          => today(),
            'imponibile'              => round($imponibile, 2),
            'iva_totale'              => round($iva, 2),
            'totale'                  => round($imponibile + $iva, 2),
            'stato'                   => StatoFatturaAcquisto::Ricevuta,
            'note'                    => 'Generata da DDT: ' . implode(', ', $ddtList->pluck('numero_ddt')->toArray()),
            'user_id'                 => auth()->id(),
        ]);

        foreach ($righe as $riga) {
            $fat->righe()->create($riga);
        }

        // Collega il primo DDT alla fattura (altri DDT citati nella nota)
        if (count($this->ddtSelezionati) === 1) {
            $fat->update(['ddt_fornitore_id' => $this->ddtSelezionati[0]]);
        }

        $this->showDdtModal = false;
        session()->flash('success', 'Fattura generata dai DDT selezionati. Verifica il numero e registra.');
    }

    // ── Import XML ────────────────────────────────────────────────────────

    public function importaXml(): void
    {
        $this->validate(['xmlFile' => 'required|file|mimes:xml,zip|max:10240']);

        $ext  = $this->xmlFile->getClientOriginalExtension();
        $path = $this->xmlFile->store('fatturapa/import', 'local');
        $full = storage_path("app/{$path}");

        $parser = app(FatturaPAParser::class);

        try {
            $risultati = $ext === 'zip'
                ? $parser->parsaZip($full)
                : [$parser->parsaFatturaAcquisto($full)];
        } catch (\Throwable $e) {
            session()->flash('error', "Errore parsing XML: {$e->getMessage()}");
            Storage::disk('local')->delete($path);
            return;
        }

        $create = 0;
        foreach ($risultati as $r) {
            if (isset($r['errore'])) continue;

            FatturaAcquisto::create([
                'fornitore_id'            => $r['fornitore_id'],
                'numero_fattura_fornitore' => $r['numero_fattura'],
                'data_fattura'            => $r['data_fattura'],
                'data_ricezione'          => today(),
                'imponibile'              => $r['imponibile'],
                'iva_totale'              => $r['iva_totale'],
                'totale'                  => $r['totale'],
                'stato'                   => StatoFatturaAcquisto::Ricevuta,
                'xml_sdi_path'            => $path,
                'note'                    => $r['fornitore_warn'] ? "ATTENZIONE: fornitore P.IVA {$r['partita_iva']} non in anagrafica." : null,
                'user_id'                 => auth()->id(),
            ]);
            $create++;
        }

        $this->xmlFile = null;
        session()->flash('success', "{$create} fattura/e importata/e da XML.");
    }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = $this->buildQuery();

        return response()->streamDownload(function () use ($query) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fornitore', 'Numero', 'Data', 'Scadenza', 'Imponibile', 'IVA', 'Totale', 'Stato'], ';');

            foreach ($query->cursor() as $f) {
                fputcsv($handle, [
                    $f->fornitore->ragione_sociale,
                    $f->numero_fattura_fornitore,
                    $f->data_fattura->format('d/m/Y'),
                    $f->data_scadenza?->format('d/m/Y') ?? '',
                    number_format((float) $f->imponibile, 2, ',', '.'),
                    number_format((float) $f->iva_totale, 2, ',', '.'),
                    number_format((float) $f->totale, 2, ',', '.'),
                    $f->stato->label(),
                ], ';');
            }

            fclose($handle);
        }, 'fatture-acquisto.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery()
    {
        return FatturaAcquisto::with(['fornitore', 'pagamenti'])
            ->when($this->search, fn($q) => $q->where('numero_fattura_fornitore', 'like', "%{$this->search}%"))
            ->when($this->filtroFornitore, fn($q) => $q->where('fornitore_id', $this->filtroFornitore))
            ->when($this->filtroStato, fn($q) => $q->where('stato', $this->filtroStato))
            ->when($this->filtroDal, fn($q) => $q->whereDate('data_fattura', '>=', $this->filtroDal))
            ->when($this->filtroAl,  fn($q) => $q->whereDate('data_fattura', '<=', $this->filtroAl))
            ->orderByDesc('data_fattura');
    }

    public function render()
    {
        return view('livewire.acquisti.lista-fatture-acquisto', [
            'fatture'  => $this->buildQuery()->paginate(20),
            'fornitori' => Fornitore::orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
            'stati'    => StatoFatturaAcquisto::cases(),
            'metodi'   => MetodoPagamentoFornitore::cases(),
        ]);
    }
}
