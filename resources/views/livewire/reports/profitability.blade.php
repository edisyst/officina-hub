<div>
  {{-- Filtri --}}
  <div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
      <div class="row align-items-end">
        <div class="col-md-2">
          <label class="mb-0 small">Dal</label>
          <input wire:model.live="dal" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="mb-0 small">Al</label>
          <input wire:model.live="al" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
          <label class="mb-0 small">Meccanico</label>
          <select wire:model.live="meccanico_id" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($meccanici as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="mb-0 small">Stato commessa</label>
          <select wire:model.live="stato" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($stati as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button wire:click="exportCsv" class="btn btn-sm btn-outline-success w-100">
            <i class="fas fa-file-csv"></i> Esporta CSV
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Totali di periodo --}}
  <div class="row">
    <div class="col-md-3">
      <div class="small-box bg-info">
        <div class="inner">
          <h3>{{ $totali['commesse_count'] }}</h3>
          <p>Commesse nel periodo</p>
        </div>
        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="small-box bg-success">
        <div class="inner">
          <h3>€ {{ number_format($totali['ricavo_totale'], 0, ',', '.') }}</h3>
          <p>Ricavo totale (imponibile)</p>
        </div>
        <div class="icon"><i class="fas fa-euro-sign"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="small-box {{ $totali['margine_perc'] >= 30 ? 'bg-success' : ($totali['margine_perc'] >= 10 ? 'bg-warning' : 'bg-danger') }}">
        <div class="inner">
          <h3>{{ $totali['margine_perc'] }}%</h3>
          <p>Margine ricambi</p>
        </div>
        <div class="icon"><i class="fas fa-chart-pie"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="small-box bg-secondary">
        <div class="inner">
          <h3>{{ $totali['ore_eff'] }}h <small>/ {{ $totali['ore_prev'] }}h</small></h3>
          <p>Ore eff. / preventivate</p>
        </div>
        <div class="icon"><i class="fas fa-clock"></i></div>
      </div>
    </div>
  </div>

  {{-- Tabella per meccanico --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-user-cog mr-1"></i>Per meccanico</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Meccanico</th>
            <th class="text-right">Ore prev.</th>
            <th class="text-right">Ore eff.</th>
            <th class="text-right">Efficienza %</th>
            <th class="text-right">Ricavo manodopera</th>
          </tr>
        </thead>
        <tbody>
          @forelse($mecRep as $m)
          <tr>
            <td>{{ $m['name'] }}</td>
            <td class="text-right">{{ number_format($m['ore_prev'], 1) }}</td>
            <td class="text-right">{{ number_format($m['ore_eff'], 1) }}</td>
            <td class="text-right">
              @if($m['efficienza'] !== null)
              <span class="{{ $m['efficienza'] >= 90 ? 'text-success' : ($m['efficienza'] >= 70 ? 'text-warning' : 'text-danger') }}">
                {{ $m['efficienza'] }}%
              </span>
              @else
              <span class="text-muted">—</span>
              @endif
            </td>
            <td class="text-right">€ {{ number_format($m['ricavo_mano'], 2, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-3">Nessuna lavorazione nel periodo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Tabella per OdL --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i>Dettaglio per commessa</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Numero</th>
            <th>Cliente</th>
            <th>Veicolo</th>
            <th>Stato</th>
            <th class="text-right">Ricavo</th>
            <th class="text-right">Ore prev.</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($commesseRep as $c)
          <tr>
            <td><a href="{{ route('commesse.show', $c->id) }}">{{ $c->numero }}</a></td>
            <td>{{ $c->cliente }}</td>
            <td>{{ $c->targa }}</td>
            <td><span class="badge badge-secondary">{{ $c->stato }}</span></td>
            <td class="text-right">€ {{ number_format((float)$c->ricavo, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format((float)$c->ore_prev, 1) }}h</td>
            <td><a href="{{ route('commesse.show', $c->id) }}" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-3">Nessuna commessa nel periodo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($commesseRep->hasPages())
    <div class="card-footer">
      {{ $commesseRep->links() }}
    </div>
    @endif
  </div>
</div>
