<div>
  {{-- Riepilogo insoluto --}}
  <div class="info-box mb-3 bg-danger text-white">
    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
    <div class="info-box-content">
      <span class="info-box-text">Totale insoluto</span>
      <span class="info-box-number">€ {{ number_format(max(0, (float)$totaleInsoluto), 2, ',', '.') }}</span>
    </div>
  </div>

  <div class="card card-outline card-warning">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Scadenziario incassi</h3>
    </div>
    <div class="card-header bg-light py-2">
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
    </div>
    @if($documenti->hasPages())
    <div class="card-footer">{{ $documenti->links() }}</div>
    @endif
  </div>

  {{-- MODAL: Pagamento rapido --}}
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
</div>
