<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('error') }}
    </div>
  @endif

  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Fatture d'acquisto</h3>
      <div class="card-tools">
        {{-- Upload XML --}}
        <form wire:submit="importaXml" class="d-inline">
          <div class="input-group input-group-sm" style="width:300px;display:inline-flex">
            <input type="file" wire:model="xmlFile" class="form-control form-control-sm" accept=".xml,.zip">
            <div class="input-group-append">
              <button type="submit" class="btn btn-outline-info">
                <i class="fas fa-file-import"></i> Importa XML
              </button>
            </div>
          </div>
        </form>

        <button wire:click="apriDdtModal" class="btn btn-sm btn-outline-warning ml-1">
          <i class="fas fa-layer-group mr-1"></i> Da DDT
        </button>
        <button wire:click="apriModal()" class="btn btn-sm btn-primary ml-1">
          <i class="fas fa-plus mr-1"></i> Nuova fattura
        </button>
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary ml-1">
          <i class="fas fa-file-csv"></i>
        </button>
      </div>
    </div>

    <div class="card-header bg-light py-2">
      <div class="row g-2">
        <div class="col-md-3">
          <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Cerca numero fattura...">
        </div>
        <div class="col-md-3">
          <select wire:model.live="filtroFornitore" class="form-control form-control-sm">
            <option value="">Tutti i fornitori</option>
            @foreach($fornitori as $f)
              <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti gli stati</option>
            @foreach($stati as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" wire:model.live="filtroDal" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <input type="date" wire:model.live="filtroAl" class="form-control form-control-sm">
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Numero</th>
              <th>Fornitore</th>
              <th>Data</th>
              <th>Scadenza</th>
              <th class="text-right">Imponibile</th>
              <th class="text-right">IVA</th>
              <th class="text-right">Totale</th>
              <th class="text-right">Pagato</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($fatture as $fat)
            @php
              $gg = $fat->data_scadenza ? now()->diffInDays($fat->data_scadenza, false) : null;
              $rowClass = match(true) {
                $fat->stato->value === 'pagata' => 'table-success',
                $gg === null => '',
                $gg < 0     => 'table-danger',
                $gg <= 30   => 'table-warning',
                default     => '',
              };
            @endphp
            <tr class="{{ $rowClass }}">
              <td>
                <strong>{{ $fat->numero_fattura_fornitore }}</strong>
                @if($fat->xml_sdi_path)
                  <span class="badge badge-light ml-1" title="Importata da XML"><i class="fas fa-file-code text-info"></i></span>
                @endif
              </td>
              <td>{{ $fat->fornitore->ragione_sociale }}</td>
              <td>{{ $fat->data_fattura->format('d/m/Y') }}</td>
              <td>
                {{ $fat->data_scadenza?->format('d/m/Y') ?? '—' }}
                @if($gg !== null && $gg < 0 && $fat->stato->value !== 'pagata')
                  <span class="badge badge-danger ml-1">Scaduta</span>
                @endif
              </td>
              <td class="text-right">€ {{ number_format((float)$fat->imponibile, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$fat->iva_totale, 2, ',', '.') }}</td>
              <td class="text-right font-weight-bold">€ {{ number_format((float)$fat->totale, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format($fat->totale_pagato, 2, ',', '.') }}</td>
              <td><span class="badge {{ $fat->stato->badgeClass() }}">{{ $fat->stato->label() }}</span></td>
              <td class="text-right text-nowrap">
                @if($fat->stato->value === 'ricevuta')
                <button wire:click="registra({{ $fat->id }})" class="btn btn-xs btn-info" title="Registra">
                  <i class="fas fa-check"></i>
                </button>
                @endif
                @if($fat->stato->accettaPagamenti())
                <button wire:click="apriPagamento({{ $fat->id }})" class="btn btn-xs btn-warning" title="Pagamento">
                  <i class="fas fa-euro-sign"></i>
                </button>
                @endif
                <button wire:click="apriModal({{ $fat->id }})" class="btn btn-xs btn-outline-primary" title="Modifica">
                  <i class="fas fa-edit"></i>
                </button>
                @if($fat->stato->value === 'ricevuta')
                <button wire:click="elimina({{ $fat->id }})" class="btn btn-xs btn-outline-danger"
                  onclick="return confirm('Eliminare questa fattura?')" title="Elimina">
                  <i class="fas fa-trash"></i>
                </button>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted py-4">Nessuna fattura trovata.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($fatture->hasPages())
    <div class="card-footer">{{ $fatture->links() }}</div>
    @endif
  </div>

  {{-- MODAL: Nuova/Modifica fattura --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-xl">
      <form wire:submit="salva">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ $editingId ? 'Modifica fattura' : 'Nuova fattura d\'acquisto' }}</h5>
            <button wire:click="$set('showModal',false)" type="button" class="close"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Fornitore *</label>
                  <select wire:model="fFornitoreId" class="form-control">
                    <option value="0">-- Seleziona --</option>
                    @foreach($fornitori as $f)
                      <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
                    @endforeach
                  </select>
                  @error('fFornitoreId') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>N. fattura fornitore *</label>
                  <input type="text" wire:model="fNumero" class="form-control">
                  @error('fNumero') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Data fattura *</label>
                  <input type="date" wire:model="fDataFattura" class="form-control">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Scadenza</label>
                  <input type="date" wire:model="fDataScadenza" class="form-control">
                </div>
              </div>
            </div>

            {{-- Righe --}}
            <h6 class="mt-2">Righe</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="thead-light">
                  <tr>
                    <th>Descrizione</th>
                    <th style="width:80px">Q.tà</th>
                    <th style="width:110px">Prezzo un. €</th>
                    <th style="width:90px">IVA %</th>
                    <th style="width:110px">Imponibile €</th>
                    <th style="width:36px"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($fRighe as $i => $riga)
                  <tr>
                    <td><input type="text" wire:model="fRighe.{{ $i }}.descrizione" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" wire:model.lazy="fRighe.{{ $i }}.quantita" class="form-control form-control-sm" wire:change="ricalcolaTotali"></td>
                    <td><input type="number" step="0.01" wire:model.lazy="fRighe.{{ $i }}.prezzo_unitario" class="form-control form-control-sm" wire:change="ricalcolaTotali"></td>
                    <td><input type="number" step="0.01" wire:model.lazy="fRighe.{{ $i }}.iva_percentuale" class="form-control form-control-sm" wire:change="ricalcolaTotali"></td>
                    <td class="text-right align-middle">€ {{ number_format((float)($riga['imponibile_riga'] ?? 0), 2, ',', '.') }}</td>
                    <td><button type="button" wire:click="rimuoviRigaFattura({{ $i }})" class="btn btn-xs btn-outline-danger"><i class="fas fa-times"></i></button></td>
                  </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-right font-weight-bold">Imponibile:</td>
                    <td class="text-right font-weight-bold">€ {{ number_format($fImponibile, 2, ',', '.') }}</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="4" class="text-right">IVA:</td>
                    <td class="text-right">€ {{ number_format($fIvaTotale, 2, ',', '.') }}</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="4" class="text-right font-weight-bold">Totale:</td>
                    <td class="text-right font-weight-bold">€ {{ number_format($fImponibile + $fIvaTotale, 2, ',', '.') }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <button type="button" wire:click="aggiungiRigaFattura" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-plus mr-1"></i> Aggiungi riga
            </button>

            <div class="form-group mt-3">
              <label>Note</label>
              <textarea wire:model="fNote" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" wire:click="$set('showModal',false)" class="btn btn-secondary">Annulla</button>
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- MODAL: Pagamento --}}
  @if($showPagamentoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="registraPagamento">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registra pagamento fornitore</h5>
            <button wire:click="$set('showPagamentoModal',false)" type="button" class="close"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Data *</label>
              <input type="date" wire:model="pagData" class="form-control">
              @error('pagData') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Importo (€) *</label>
              <input type="number" step="0.01" wire:model="pagImporto" class="form-control">
              @error('pagImporto') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Metodo *</label>
              <select wire:model="pagMetodo" class="form-control">
                @foreach($metodi as $m)
                  <option value="{{ $m->value }}">{{ $m->label() }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group mb-0">
              <label>Riferimento (es: numero bonifico)</label>
              <input type="text" wire:model="pagRiferimento" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" wire:click="$set('showPagamentoModal',false)" class="btn btn-secondary">Annulla</button>
            <button type="submit" class="btn btn-warning">Salva pagamento</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- MODAL: Fattura da DDT --}}
  @if($showDdtModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Genera fattura da DDT fornitore</h5>
          <button wire:click="$set('showDdtModal',false)" type="button" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-5">
              <label>Fornitore *</label>
              <select wire:model="ddtFornitoreId" class="form-control">
                <option value="0">-- Seleziona --</option>
                @foreach($fornitori as $f)
                  <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label>Dal</label>
              <input type="date" wire:model="ddtDal" class="form-control">
            </div>
            <div class="col-md-3">
              <label>Al</label>
              <input type="date" wire:model="ddtAl" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button wire:click="cercaDdt" type="button" class="btn btn-outline-primary">Cerca</button>
            </div>
          </div>

          @if(count($ddtDisponibili) > 0)
          <table class="table table-sm table-bordered">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">
                  <input type="checkbox" wire:model="ddtSelezionati" value="all"
                    onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c => c.checked = this.checked)">
                </th>
                <th>N. DDT</th>
                <th>Data</th>
                <th>Righe</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ddtDisponibili as $ddt)
              <tr>
                <td>
                  <input type="checkbox" wire:model="ddtSelezionati" value="{{ $ddt['id'] }}">
                </td>
                <td>{{ $ddt['numero_ddt'] }}</td>
                <td>{{ $ddt['data_ddt'] }}</td>
                <td>{{ $ddt['righe'] }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @elseif($ddtFornitoreId)
          <p class="text-muted">Nessun DDT non ancora fatturato nel periodo selezionato.</p>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" wire:click="$set('showDdtModal',false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="creaFatturaDaDdt" type="button" class="btn btn-warning"
            {{ empty($ddtSelezionati) ? 'disabled' : '' }}>
            Genera fattura
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
