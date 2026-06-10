<div>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    Documento operativo ad uso interno — <strong>NON HA VALORE FISCALE</strong>.
    Utilizzarlo come base per il briefing con il commercialista.
  </div>

  <div class="row mb-3">
    <div class="col-md-3">
      <label>Dal</label>
      <input type="date" wire:model.live="filtroDal" class="form-control">
    </div>
    <div class="col-md-3">
      <label>Al</label>
      <input type="date" wire:model.live="filtroAl" class="form-control">
    </div>
    <div class="col-md-6 d-flex align-items-end">
      <button wire:click="generaPdf" class="btn btn-danger mr-2">
        <span wire:loading.remove wire:target="generaPdf">
          <i class="fas fa-file-pdf mr-1"></i> Scarica PDF riepilogo
        </span>
        <span wire:loading wire:target="generaPdf">
          <i class="fas fa-spinner fa-spin mr-1"></i> Generazione...
        </span>
      </button>
      <button wire:click="esportaRegistroIva" class="btn btn-outline-secondary">
        <span wire:loading.remove wire:target="esportaRegistroIva">
          <i class="fas fa-download mr-1"></i> Export registro IVA ({{ $formatoAttuale }})
        </span>
        <span wire:loading wire:target="esportaRegistroIva">
          <i class="fas fa-spinner fa-spin mr-1"></i> Export...
        </span>
      </button>
    </div>
  </div>

  {{-- Riepilogo fatture --}}
  <div class="card card-outline card-secondary mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Fatture emesse</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Aliquota / Natura</th>
            <th class="text-right">Imponibile</th>
            <th class="text-right">IVA</th>
            <th class="text-right">Totale</th>
          </tr>
        </thead>
        <tbody>
          @forelse($perAliquota as $aliquota => $riga)
          <tr>
            <td>{{ $aliquota }}</td>
            <td class="text-right">€ {{ number_format($riga['imponibile'], 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($riga['iva'], 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($riga['totale'], 2, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-muted py-3">Nessuna fattura nel periodo.</td></tr>
          @endforelse
        </tbody>
        @if($perAliquota->isNotEmpty())
        <tfoot class="font-weight-bold table-secondary">
          <tr>
            <td>Totale</td>
            <td class="text-right">€ {{ number_format($perAliquota->sum('imponibile'), 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($perAliquota->sum('iva'), 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($totaleDocumenti, 2, ',', '.') }}</td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>

  {{-- Pagamenti --}}
  <div class="row mb-3">
    <div class="col-md-4">
      <div class="info-box bg-success">
        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Incassato</span>
          <span class="info-box-number">€ {{ number_format($totalePagato, 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-box bg-warning">
        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Insoluto</span>
          <span class="info-box-number">€ {{ number_format($insoluto, 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-box bg-secondary">
        <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Documenti emessi</span>
          <span class="info-box-number">{{ $documenti->count() }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Prima nota --}}
  <div class="card card-outline card-secondary mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>Prima nota periodo</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Data</th><th>Causale</th><th>Tipo</th><th>Metodo</th><th class="text-right">Importo</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movimentiPrimaNota as $m)
          <tr>
            <td>{{ $m->data->format('d/m/Y') }}</td>
            <td>{{ $m->causale }}</td>
            <td><span class="badge {{ $m->tipo->badgeClass() }}">{{ $m->tipo->label() }}</span></td>
            <td class="small">{{ $m->metodo->label() }}</td>
            <td class="text-right">€ {{ number_format((float)$m->importo, 2, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-3">Nessun movimento registrato nel periodo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Formato export --}}
  <div class="card card-outline card-info">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Formato export contabile</h3>
    </div>
    <div class="card-body">
      <p class="text-muted small">
        Formato attuale: <strong>{{ $formatoAttuale }}</strong>.
        Modificabile in <a href="{{ route('settings.index') }}">Impostazioni &gt; Export contabile</a>.
      </p>
      <div class="row">
        @foreach($formati as $f)
        <div class="col-md-3 mb-2">
          <span class="badge {{ $f->value === $formatoAttuale ? 'badge-primary' : 'badge-light' }} p-2">
            {{ $f->label() }}
          </span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
