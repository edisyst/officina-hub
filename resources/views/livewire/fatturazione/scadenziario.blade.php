<div>
  <div class="card card-outline card-warning">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Scadenziario</h3>
    </div>

    {{-- Tab selezione --}}
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <a class="nav-link {{ $tab === 'clienti' ? 'active' : '' }}"
             wire:click="$set('tab','clienti')" href="#" role="tab">
            <i class="fas fa-arrow-down text-success mr-1"></i> Incassi clienti
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab === 'fornitori' ? 'active' : '' }}"
             wire:click="$set('tab','fornitori')" href="#" role="tab">
            <i class="fas fa-arrow-up text-danger mr-1"></i> Pagamenti fornitori
          </a>
        </li>
      </ul>
    </div>

    @if($tab === 'clienti')
    {{-- Riepilogo insoluto clienti --}}
    <div class="info-box mb-0 bg-danger text-white mx-3 mt-3">
      <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Totale insoluto clienti</span>
        <span class="info-box-number">€ {{ number_format(max(0, (float)$totaleInsoluto), 2, ',', '.') }}</span>
      </div>
    </div>
    @else
    {{-- Riepilogo dovuto fornitori --}}
    <div class="info-box mb-0 bg-warning text-white mx-3 mt-3">
      <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Da pagare fornitori</span>
        <span class="info-box-number">€ {{ number_format(max(0, (float)$totaleDovuto), 2, ',', '.') }}</span>
      </div>
    </div>
    @endif

    <div class="card-header bg-light py-2 mt-3">
      <div class="row g-2">
        <div class="col-md-3">
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti gli stati</option>
            @foreach($stati as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" wire:model.live="dataDa" class="form-control form-control-sm" placeholder="Da">
        </div>
        <div class="col-md-3">
          <input type="date" wire:model.live="dataA" class="form-control form-control-sm" placeholder="A">
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      @if(session('success'))
        <div class="alert alert-success m-3 alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          {{ session('success') }}
        </div>
      @endif

      @if($tab === 'clienti' && $documenti)
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Numero</th>
              <th>Cliente</th>
              <th>Emessa</th>
              <th>Scadenza</th>
              <th class="text-right">Totale</th>
              <th class="text-right">Pagato</th>
              <th class="text-right">Saldo</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($documenti as $doc)
            @php
              $gg = $doc->data_scadenza ? now()->diffInDays($doc->data_scadenza, false) : null;
              $rowClass = match(true) {
                $gg === null  => '',
                $gg < 0       => 'table-danger',
                $gg <= 30     => 'table-warning',
                default       => 'table-success',
              };
            @endphp
            <tr class="{{ $rowClass }}">
              <td><a href="{{ route('fatturazione.documenti.show', $doc->id) }}">{{ $doc->numero }}</a></td>
              <td>{{ $doc->cliente->nome_completo }}</td>
              <td>{{ $doc->data_emissione->format('d/m/Y') }}</td>
              <td>
                {{ $doc->data_scadenza?->format('d/m/Y') ?? '—' }}
                @if($gg !== null && $gg < 0)
                  <span class="badge badge-danger ml-1">Scaduta {{ abs((int)$gg) }}gg</span>
                @elseif($gg !== null && $gg <= 30)
                  <span class="badge badge-warning ml-1">{{ (int)$gg }}gg</span>
                @endif
              </td>
              <td class="text-right">€ {{ number_format((float)$doc->totale, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format($doc->totale_pagato, 2, ',', '.') }}</td>
              <td class="text-right font-weight-bold">€ {{ number_format($doc->saldo, 2, ',', '.') }}</td>
              <td><span class="badge {{ $doc->stato->badgeClass() }}">{{ $doc->stato->label() }}</span></td>
              <td>
                @if($doc->stato->accettaPagamenti())
                <button wire:click="apriPagamento({{ $doc->id }})" class="btn btn-xs btn-warning">
                  <i class="fas fa-euro-sign"></i>
                </button>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Nessuna fattura in scadenza.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($documenti->hasPages())
      <div class="card-footer">{{ $documenti->links() }}</div>
      @endif

      @elseif($tab === 'fornitori' && $fatture)
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>N. Fattura</th>
              <th>Fornitore</th>
              <th>Data</th>
              <th>Scadenza</th>
              <th class="text-right">Totale</th>
              <th class="text-right">Pagato</th>
              <th class="text-right">Saldo</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($fatture as $fat)
            @php
              $gg = $fat->data_scadenza ? now()->diffInDays($fat->data_scadenza, false) : null;
              $rowClass = match(true) {
                $gg === null => '',
                $gg < 0      => 'table-danger',
                $gg <= 30    => 'table-warning',
                default      => '',
              };
            @endphp
            <tr class="{{ $rowClass }}">
              <td><strong>{{ $fat->numero_fattura_fornitore }}</strong></td>
              <td>{{ $fat->fornitore->ragione_sociale }}</td>
              <td>{{ $fat->data_fattura->format('d/m/Y') }}</td>
              <td>
                {{ $fat->data_scadenza?->format('d/m/Y') ?? '—' }}
                @if($gg !== null && $gg < 0)
                  <span class="badge badge-danger ml-1">Scaduta {{ abs((int)$gg) }}gg</span>
                @elseif($gg !== null && $gg <= 30)
                  <span class="badge badge-warning ml-1">{{ (int)$gg }}gg</span>
                @endif
              </td>
              <td class="text-right">€ {{ number_format((float)$fat->totale, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format($fat->totale_pagato, 2, ',', '.') }}</td>
              <td class="text-right font-weight-bold">€ {{ number_format($fat->saldo, 2, ',', '.') }}</td>
              <td><span class="badge {{ $fat->stato->badgeClass() }}">{{ $fat->stato->label() }}</span></td>
              <td>
                @if($fat->stato->accettaPagamenti())
                <button wire:click="apriPagamentoFornitore({{ $fat->id }})" class="btn btn-xs btn-warning">
                  <i class="fas fa-euro-sign"></i>
                </button>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Nessuna fattura fornitore in scadenza.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($fatture->hasPages())
      <div class="card-footer">{{ $fatture->links() }}</div>
      @endif
      @endif
    </div>
  </div>

  {{-- MODAL: Pagamento rapido clienti --}}
  @if($showPagamentoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="registraPagamento">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registra pagamento rapido</h5>
            <button wire:click="$set('showPagamentoModal',false)" type="button" class="close"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Data *</label>
              <input type="date" class="form-control" wire:model="pagDataPagamento">
              @error('pagDataPagamento') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label>Importo (€) *</label>
              <input type="number" step="0.01" class="form-control" wire:model="pagImporto">
              @error('pagImporto') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="form-group mb-0">
              <label>Metodo *</label>
              <select class="form-control" wire:model="pagMetodo">
                @foreach($metodiPagamento as $mp)
                  <option value="{{ $mp->value }}">{{ $mp->label() }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" wire:click="$set('showPagamentoModal',false)" class="btn btn-secondary">Annulla</button>
            <button type="submit" class="btn btn-warning">Salva</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- MODAL: Pagamento fornitore --}}
  @if($showPagamentoFornitoreModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="registraPagamentoFornitore">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registra pagamento fornitore</h5>
            <button wire:click="$set('showPagamentoFornitoreModal',false)" type="button" class="close"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Data *</label>
              <input type="date" class="form-control" wire:model="pagFornitoreData">
            </div>
            <div class="form-group">
              <label>Importo (€) *</label>
              <input type="number" step="0.01" class="form-control" wire:model="pagFornitoreImporto">
            </div>
            <div class="form-group">
              <label>Metodo *</label>
              <select class="form-control" wire:model="pagFornitoreMetodo">
                @foreach($metodiFornitore as $m)
                  <option value="{{ $m->value }}">{{ $m->label() }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group mb-0">
              <label>Riferimento (n. bonifico, ecc.)</label>
              <input type="text" class="form-control" wire:model="pagFornitoreRiferimento">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" wire:click="$set('showPagamentoFornitoreModal',false)" class="btn btn-secondary">Annulla</button>
            <button type="submit" class="btn btn-warning">Salva</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif
</div>
