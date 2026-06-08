<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Report Cortesia</h3>
      <div class="card-tools d-flex">
        <select class="form-control form-control-sm mr-2" wire:model.live="filtroAnno" style="width:auto">
          @foreach($anni as $a)
          <option value="{{ $a }}">{{ $a }}</option>
          @endforeach
        </select>
        <button class="btn btn-sm btn-outline-secondary" wire:click="esportaCsv">
          <i class="fas fa-download mr-1"></i> CSV
        </button>
      </div>
    </div>
  </div>

  {{-- Prestiti in ritardo --}}
  @if($prestitiInRitardo->count())
  <div class="card border-danger">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Prestiti in ritardo ({{ $prestitiInRitardo->count() }})</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Veicolo</th><th>Cliente</th><th>Rientro previsto</th><th>Giorni ritardo</th><th></th></tr></thead>
        <tbody>
          @foreach($prestitiInRitardo as $p)
          <tr>
            <td><strong>{{ $p->veicolo->targa }}</strong> {{ $p->veicolo->marca }} {{ $p->veicolo->modello }}</td>
            <td>{{ $p->cliente->nome_completo }}</td>
            <td>{{ $p->data_rientro_prevista->format('d/m/Y') }}</td>
            <td><span class="badge badge-danger">+{{ now()->diffInDays($p->data_rientro_prevista) }} gg</span></td>
            <td>
              <a href="{{ route('cortesia.rientro', $p->id) }}" class="btn btn-xs btn-warning">Registra rientro</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- Utilizzo per veicolo --}}
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Utilizzo per veicolo ({{ $filtroAnno }})</h5></div>
    <div class="card-body p-0">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Veicolo</th>
            <th>N. prestiti</th>
            <th>Giorni prestati</th>
            <th>Km totali</th>
            <th>Cauzione totale</th>
          </tr>
        </thead>
        <tbody>
          @forelse($utilizzoPerVeicolo as $row)
          <tr>
            <td>
              <strong>{{ $row['veicolo']->targa }}</strong>
              <span class="text-muted">{{ $row['veicolo']->marca }} {{ $row['veicolo']->modello }}</span>
              @if(!$row['veicolo']->attivo)<span class="badge badge-secondary">Inattivo</span>@endif
            </td>
            <td>{{ $row['n_prestiti'] }}</td>
            <td>{{ $row['giorni_prestati'] }}</td>
            <td>{{ number_format($row['km_totali'], 0, ',', '.') }}</td>
            <td>€ {{ number_format($row['cauzione_totale'], 2, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-3">Nessun dato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
