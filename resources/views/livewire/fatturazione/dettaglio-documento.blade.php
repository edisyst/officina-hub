<div>
  {{-- Intestazione documento --}}
  <div class="card card-primary card-outline">
    <div class="card-header d-flex align-items-center">
      <h3 class="card-title">
        <span class="badge {{ $documento->tipo->badgeClass() }} mr-2">{{ $documento->tipo->label() }}</span>
        <strong>{{ $documento->numero }}</strong>
      </h3>
      <span class="ml-3 badge {{ $documento->stato->badgeClass() }}">{{ $documento->stato->label() }}</span>
      <div class="card-tools ml-auto">
        {{-- Pulsanti contestuali per stato --}}
        @if($documento->stato->value === 'bozza')
          <button wire:click="$set('showEmissioneModal', true)" class="btn btn-sm btn-success">
            <i class="fas fa-paper-plane mr-1"></i> Emetti fattura
          </button>
        @endif
        @if(in_array($documento->stato->value, ['emessa','inviata_sdi','scartata_sdi']))
          <button wire:click="generaXml" class="btn btn-sm btn-info" wire:loading.attr="disabled">
            <span wire:loading wire:target="generaXml"><i class="fas fa-spinner fa-spin mr-1"></i></span>
            <span wire:loading.remove wire:target="generaXml"><i class="fas fa-code mr-1"></i></span>
            Genera XML FatturaPA
          </button>
          @if($documento->xml_generato)
            <a href="{{ route('fatturazione.documenti.xml', $documento->id) }}" class="btn btn-sm btn-outline-info" target="_blank">
              <i class="fas fa-download mr-1"></i> Scarica XML
            </a>
            <a href="{{ route('fatturazione.documenti.zip', $documento->id) }}" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-file-archive mr-1"></i> ZIP SdI
            </a>
            @if(config('sdi.abilitato'))
            <button wire:click="inviaSdi" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
              <span wire:loading wire:target="inviaSdi"><i class="fas fa-spinner fa-spin mr-1"></i></span>
              <span wire:loading.remove wire:target="inviaSdi"><i class="fas fa-cloud-upload-alt mr-1"></i></span>
              Invia al SdI
            </button>
            @endif
          @endif
        @endif
        @if(!in_array($documento->stato->value, ['bozza','annullata','pagata']))
          <a href="{{ route('fatturazione.documenti.pdf', $documento->id) }}" class="btn btn-sm btn-outline-dark" target="_blank">
            <i class="fas fa-file-pdf mr-1"></i> PDF cortesia
          </a>
        @endif
        @if($documento->stato->accettaPagamenti())
          <button wire:click="$set('showPagamentoModal', true)" class="btn btn-sm btn-warning">
            <i class="fas fa-euro-sign mr-1"></i> Registra pagamento
          </button>
        @endif
        @if(in_array($documento->stato->value, ['emessa','inviata_sdi','accettata_sdi']))
          <button wire:click="apriFirmaNotaCredito" class="btn btn-sm btn-danger">
            <i class="fas fa-times mr-1"></i> Nota di credito
          </button>
        @endif
      </div>
    </div>

    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          {{ session('success') }}
        </div>
      @endif

      {{-- Risultato validazione XML --}}
      @if($xmlValido === true)
        <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i> XML valido — conforme a FatturaPA FPR12.</div>
      @elseif($xmlValido === false)
        <div class="alert alert-danger">
          <strong><i class="fas fa-exclamation-triangle mr-1"></i> Errori di validazione XSD:</strong>
          <ul class="mb-0 mt-1">
            @foreach($xmlErrori as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm table-borderless">
            <tr><th style="width:40%">Data emissione</th><td>{{ $documento->data_emissione->format('d/m/Y') }}</td></tr>
            @if($documento->data_scadenza)
            <tr><th>Scadenza</th><td>{{ $documento->data_scadenza->format('d/m/Y') }}</td></tr>
            @endif
            @if($documento->metodo_pagamento)
            <tr><th>Metodo pagamento</th><td>{{ $documento->metodo_pagamento->label() }}</td></tr>
            @endif
            @if($documento->commessa)
            <tr><th>Commessa</th><td><a href="{{ route('commesse.show', $documento->commessa_id) }}">{{ $documento->commessa->numero }}</a></td></tr>
            @endif
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm table-borderless">
            <tr><th style="width:40%">Cliente</th><td>{{ $documento->cliente->nome_completo }}</td></tr>
            @if($documento->cliente->partita_iva)
            <tr><th>P.IVA</th><td>{{ $documento->cliente->partita_iva }}</td></tr>
            @endif
            @if($documento->cliente->codice_fiscale)
            <tr><th>C.F.</th><td>{{ $documento->cliente->codice_fiscale }}</td></tr>
            @endif
            @if($documento->cliente->codice_destinatario_sdi)
            <tr><th>Cod. SdI</th><td><code>{{ $documento->cliente->codice_destinatario_sdi }}</code></td></tr>
            @endif
          </table>
        </div>
      </div>

      @if($documento->note)
        <div class="callout callout-info"><strong>Note:</strong> {{ $documento->note }}</div>
      @endif

      {{-- Righe documento --}}
      <h5 class="border-bottom pb-1 mt-3">Righe</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Descrizione</th>
              <th class="text-right">Qtà</th>
              <th class="text-right">Prezzo</th>
              <th class="text-right">Sconto %</th>
              <th class="text-right">IVA %</th>
              <th class="text-right">Imponibile</th>
              <th class="text-right">IVA</th>
            </tr>
          </thead>
          <tbody>
            @foreach($documento->righe as $i => $riga)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ $riga->descrizione }}</td>
              <td class="text-right">{{ number_format((float)$riga->quantita, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$riga->prezzo_unitario, 2, ',', '.') }}</td>
              <td class="text-right">{{ $riga->sconto_percentuale > 0 ? $riga->sconto_percentuale.'%' : '—' }}</td>
              <td class="text-right">
                @if($riga->natura_iva) N{{ $riga->natura_iva }}
                @else {{ $riga->iva_percentuale }}%
                @endif
              </td>
              <td class="text-right">€ {{ number_format((float)$riga->imponibile_riga, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$riga->iva_riga, 2, ',', '.') }}</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot class="font-weight-bold">
            <tr>
              <td colspan="6" class="text-right">Totale imponibile</td>
              <td class="text-right">€ {{ number_format((float)$documento->imponibile, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$documento->iva_totale, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-primary">
              <td colspan="7" class="text-right"><strong>TOTALE DOCUMENTO</strong></td>
              <td class="text-right"><strong>€ {{ number_format((float)$documento->totale, 2, ',', '.') }}</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>

      {{-- Pagamenti registrati --}}
      @if($documento->pagamenti->isNotEmpty())
      <h5 class="border-bottom pb-1 mt-3">Pagamenti ricevuti</h5>
      <table class="table table-sm">
        <thead class="thead-light"><tr><th>Data</th><th>Metodo</th><th class="text-right">Importo</th><th>Riferimento</th><th>Registrato da</th></tr></thead>
        <tbody>
          @foreach($documento->pagamenti as $pag)
          <tr>
            <td>{{ $pag->data_pagamento->format('d/m/Y') }}</td>
            <td>{{ $pag->metodo->label() }}</td>
            <td class="text-right">€ {{ number_format((float)$pag->importo, 2, ',', '.') }}</td>
            <td>{{ $pag->riferimento ?? '—' }}</td>
            <td>{{ $pag->user->name ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
          <tr>
            <td colspan="2" class="text-right">Totale pagato</td>
            <td class="text-right">€ {{ number_format($documento->totale_pagato, 2, ',', '.') }}</td>
            <td colspan="2"></td>
          </tr>
          @if($documento->saldo > 0)
          <tr class="text-danger">
            <td colspan="2" class="text-right">Saldo residuo</td>
            <td class="text-right">€ {{ number_format($documento->saldo, 2, ',', '.') }}</td>
            <td colspan="2"></td>
          </tr>
          @endif
        </tfoot>
      </table>
      @endif

      {{-- Upload ricevuta SdI (visibile per documenti emessi/inviati/accettati/scartati) --}}
      @if(!in_array($documento->stato->value, ['bozza','pagata','annullata']))
      <h5 class="border-bottom pb-1 mt-3">Ricevute SdI</h5>

      {{-- Log SdI --}}
      @if($documento->sdiLog->isNotEmpty())
      <ul class="list-unstyled small text-muted mb-2">
        @foreach($documento->sdiLog->take(5) as $log)
          <li><i class="fas fa-history mr-1"></i> {{ $log->created_at->format('d/m/Y H:i') }} — <strong>{{ $log->azione }}</strong>: {{ $log->esito }} {{ $log->dettaglio ? '— '.$log->dettaglio : '' }}</li>
        @endforeach
      </ul>
      @endif

      <form wire:submit="uploadRicevuta">
        <div x-data="{ dragover: false }"
             @dragover.prevent="dragover = true"
             @dragleave.prevent="dragover = false"
             @drop.prevent="
               dragover = false;
               const dt = new DataTransfer();
               if($event.dataTransfer.files[0]) {
                 dt.items.add($event.dataTransfer.files[0]);
                 $refs.ricevutaInput.files = dt.files;
                 $refs.ricevutaInput.dispatchEvent(new Event('change'));
               }
             "
             :class="dragover ? 'border-primary bg-light' : 'border-secondary'"
             class="border border-dashed rounded p-3 text-center mb-2">
          <p class="mb-1"><i class="fas fa-cloud-upload-alt fa-2x text-muted"></i></p>
          <p class="mb-0">Trascina qui il file XML ricevuta SdI, oppure
            <label for="ricevuta-input" class="text-primary" style="cursor:pointer">seleziona file</label>
          </p>
          <input type="file" id="ricevuta-input" x-ref="ricevutaInput"
                 wire:model="ricevutaFile" class="d-none" accept=".xml">
        </div>
        @error('ricevutaFile') <div class="text-danger small">{{ $message }}</div> @enderror
        <button type="submit" class="btn btn-sm btn-outline-primary" wire:loading.attr="disabled">
          <span wire:loading wire:target="uploadRicevuta"><i class="fas fa-spinner fa-spin mr-1"></i></span>
          Carica ricevuta
        </button>
      </form>
      @endif

    </div>{{-- card-body --}}
  </div>

  {{-- MODAL: Conferma emissione --}}
  @if($showEmissioneModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Conferma emissione fattura</h5>
          <button wire:click="$set('showEmissioneModal',false)" type="button" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p>Stai per emettere la fattura <strong>{{ $documento->numero }}</strong>.</p>
          <div class="alert alert-warning mb-0">
            <i class="fas fa-lock mr-1"></i> Una volta emessa, la fattura diventa <strong>immutabile</strong>.
            Per eventuali correzioni sarà necessario emettere una nota di credito.
          </div>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showEmissioneModal',false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="emetti" class="btn btn-success">Emetti fattura</button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- MODAL: Registra pagamento --}}
  @if($showPagamentoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="registraPagamento">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registra pagamento — {{ $documento->numero }}</h5>
            <button wire:click="$set('showPagamentoModal',false)" type="button" class="close"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Data pagamento *</label>
              <input type="date" class="form-control @error('pagDataPagamento') is-invalid @enderror"
                     wire:model="pagDataPagamento">
              @error('pagDataPagamento') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Importo (€) *</label>
              <input type="number" step="0.01" min="0.01" class="form-control @error('pagImporto') is-invalid @enderror"
                     wire:model="pagImporto">
              <small class="form-text text-muted">Saldo residuo: € {{ number_format($documento->saldo, 2, ',', '.') }}</small>
              @error('pagImporto') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Metodo *</label>
              <select class="form-control @error('pagMetodo') is-invalid @enderror" wire:model="pagMetodo">
                @foreach($metodiPagamento as $mp)
                  <option value="{{ $mp->value }}">{{ $mp->label() }}</option>
                @endforeach
              </select>
              @error('pagMetodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Riferimento</label>
              <input type="text" class="form-control" wire:model="pagRiferimento" placeholder="Es. n. bonifico, auth carta...">
            </div>
            <div class="form-group mb-0">
              <label>Note</label>
              <textarea class="form-control" wire:model="pagNote" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" wire:click="$set('showPagamentoModal',false)" class="btn btn-secondary">Annulla</button>
            <button type="submit" class="btn btn-warning">Registra pagamento</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- MODAL: Conferma nota di credito --}}
  @if($showNotaCreditoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Emetti nota di credito</h5>
          <button wire:click="$set('showNotaCreditoModal',false)" type="button" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p>Stai per emettere una <strong>nota di credito</strong> per annullare la fattura
            <strong>{{ $documento->numero }}</strong> (€ {{ number_format((float)$documento->totale, 2, ',', '.') }}).</p>
          <div class="alert alert-danger mb-0">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            La fattura originale sarà <strong>annullata</strong>. Operazione irreversibile.
          </div>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showNotaCreditoModal',false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="emettereNotaCredito" class="btn btn-danger">Conferma nota di credito</button>
        </div>
      </div>
    </div>
  </div>
  @endif

</div>
