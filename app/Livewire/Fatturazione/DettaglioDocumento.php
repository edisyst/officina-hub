<?php

namespace App\Livewire\Fatturazione;

use App\Actions\Fatturazione\EmettereNotaCreditoAction;
use App\Enums\MetodoPagamento;
use App\Enums\StatoDocumento;
use App\Models\Documento;
use App\Services\FatturaPAService;
use DOMDocument;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class DettaglioDocumento extends Component
{
    use WithFileUploads;

    public Documento $documento;

    // Validazione XML
    public ?bool $xmlValido = null;
    public array $xmlErrori = [];

    // Modal pagamento
    public bool $showPagamentoModal = false;

    #[Rule('required|date')]
    public string $pagDataPagamento = '';

    #[Rule('required|numeric|min:0.01')]
    public float $pagImporto = 0;

    #[Rule('required|in:contanti,bonifico,carta,assegno,rid')]
    public string $pagMetodo = 'contanti';

    #[Rule('nullable|string|max:255')]
    public string $pagRiferimento = '';

    #[Rule('nullable|string|max:500')]
    public string $pagNote = '';

    // Modal conferma emissione
    public bool $showEmissioneModal = false;

    // Modal conferma nota di credito
    public bool $showNotaCreditoModal = false;

    // Upload ricevuta SdI
    public $ricevutaFile;

    public function mount(int $documentoId): void
    {
        $this->documento = Documento::with(['cliente', 'righe', 'pagamenti.user', 'sdiLog'])
            ->findOrFail($documentoId);

        $this->authorize('view', $this->documento);
        $this->pagDataPagamento = now()->toDateString();
        $this->pagImporto       = (float) $this->documento->saldo;
    }

    /** Genera e valida l'XML FatturaPA, salva sul documento se valido */
    public function generaXml(): void
    {
        $this->authorize('generaXml', $this->documento);

        $service = app(FatturaPAService::class);
        $xml     = $service->genera($this->documento);
        $result  = $service->valida($xml);

        $this->xmlValido = $result['valido'];
        $this->xmlErrori = $result['errori'];

        if ($result['valido']) {
            $nomeFile = $service->nomeFile($this->documento);
            $this->documento->update([
                'xml_generato'  => $xml,
                'xml_hash'      => hash('sha256', $xml),
                'nome_file_sdi' => $nomeFile,
            ]);
            $service->log($this->documento, 'genera_xml', 'successo', $nomeFile);
            session()->flash('success', 'XML generato e validato correttamente.');
        } else {
            $service->log($this->documento, 'genera_xml', 'errore', implode('; ', $result['errori']));
        }

        $this->documento->refresh();
    }

    /** Passa il documento da bozza a emessa */
    public function emetti(): void
    {
        $this->authorize('emetti', $this->documento);

        $this->documento->update(['stato' => StatoDocumento::Emessa]);
        $this->documento->refresh();
        $this->showEmissioneModal = false;
        session()->flash('success', 'Fattura emessa correttamente.');
    }

    /** Registra un pagamento e aggiorna lo stato del documento se pagato */
    public function registraPagamento(): void
    {
        $this->authorize('registraPagamento', $this->documento);
        $this->validate([
            'pagDataPagamento' => 'required|date',
            'pagImporto'       => 'required|numeric|min:0.01',
            'pagMetodo'        => 'required|in:contanti,bonifico,carta,assegno,rid',
            'pagRiferimento'   => 'nullable|string|max:255',
            'pagNote'          => 'nullable|string|max:500',
        ]);

        $this->documento->pagamenti()->create([
            'data_pagamento' => $this->pagDataPagamento,
            'importo'        => $this->pagImporto,
            'metodo'         => $this->pagMetodo,
            'riferimento'    => $this->pagRiferimento ?: null,
            'note'           => $this->pagNote ?: null,
            'user_id'        => auth()->id(),
        ]);

        // Aggiorna lo stato a "pagata" se il saldo è azzerato
        $this->documento->load('pagamenti');
        if ($this->documento->totale_pagato >= (float) $this->documento->totale) {
            $this->documento->update(['stato' => StatoDocumento::Pagata]);
        }

        $this->documento->refresh()->load(['pagamenti.user']);
        $this->showPagamentoModal = false;
        $this->pagImporto         = (float) $this->documento->saldo;
        session()->flash('success', 'Pagamento registrato.');
    }

    /** Apre il modal per la nota di credito */
    public function apriFirmaNotaCredito(): void
    {
        $this->authorize('annullaConNotaCredito', $this->documento);
        $this->showNotaCreditoModal = true;
    }

    /** Emette la nota di credito e annulla la fattura originale */
    public function emettereNotaCredito(): void
    {
        $this->authorize('annullaConNotaCredito', $this->documento);

        $notaCredito = app(EmettereNotaCreditoAction::class)->execute($this->documento);

        $this->showNotaCreditoModal = false;
        $this->documento->refresh();

        session()->flash('success', "Nota di credito {$notaCredito->numero} creata. Fattura annullata.");
    }

    /** Upload e parsing ricevuta SdI (NS/RC/MC/NE) */
    public function uploadRicevuta(): void
    {
        $this->validate(['ricevutaFile' => 'required|file|max:512']);

        $xmlContent = file_get_contents($this->ricevutaFile->getRealPath());

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $ok = @$dom->loadXML($xmlContent);
        libxml_clear_errors();

        if (!$ok) {
            $this->addError('ricevutaFile', 'Il file non è un XML valido.');
            return;
        }

        $tipoRicevuta = $dom->documentElement?->localName ?? 'Sconosciuta';

        // Mappa tipo ricevuta → nuovo stato documento
        $nuovoStato = match($tipoRicevuta) {
            'NotificaScarto'           => StatoDocumento::ScartatasSdi,
            'RicevutaConsegna'         => StatoDocumento::AccettataSdi,
            'NotificaMancataConsegna'  => StatoDocumento::InviataSdi,
            default                    => null,
        };

        // Salva il file
        $dir = storage_path('app/fatturapa/ricevute');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = ($this->documento->nome_file_sdi ?: $this->documento->numero)
            . '_' . $tipoRicevuta . '.xml';
        file_put_contents($dir . '/' . $filename, $xmlContent);

        if ($nuovoStato) {
            $this->documento->update(['stato' => $nuovoStato]);
        }

        app(FatturaPAService::class)->log(
            $this->documento,
            'upload_ricevuta',
            'successo',
            "Tipo: {$tipoRicevuta}, file: {$filename}"
        );

        $this->documento->refresh()->load('sdiLog');
        $this->ricevutaFile = null;
        session()->flash('success', "Ricevuta {$tipoRicevuta} caricata. Stato aggiornato.");
    }

    public function render()
    {
        return view('livewire.fatturazione.dettaglio-documento', [
            'metodiPagamento' => MetodoPagamento::cases(),
        ]);
    }
}
