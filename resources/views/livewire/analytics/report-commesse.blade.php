<div>

  {{-- Filtri --}}
  <div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
      <div class="row align-items-center flex-wrap g-2">

        {{-- Periodo --}}
        <div class="col-auto">
          <label class="mb-0 font-weight-bold small">Periodo:</label>
          <select wire:model.live="periodo" class="form-control form-control-sm">
            <option value="questo_mese">Questo mese</option>
            <option value="mese_scorso">Mese scorso</option>
            <option value="questo_trimestre">Questo trimestre</option>
            <option value="questo_anno">Questo anno</option>
            <option value="personalizzato">Personalizzato</option>
          </select>
        </div>

        @if($periodo === 'personalizzato')
        <div class="col-auto d-flex align-items-end">
          <input type="date" wire:model.defer="dataDa" class="form-control form-control-sm mr-1" style="width:140px">
          <input type="date" wire:model.defer="dataA"  class="form-control form-control-sm mr-1" style="width:140px">
          <button wire:click="applicaFiltroPersonalizzato" class="btn btn-sm btn-primary">
            <i class="fas fa-filter"></i>
          </button>
        </div>
        @endif

        {{-- Stato --}}
        <div class="col-auto">
          <label class="mb-0 font-weight-bold small">Stato:</label>
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($statiList as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>

        {{-- Tipo --}}
        <div class="col-auto">
          <label class="mb-0 font-weight-bold small">Tipo:</label>
          <select wire:model.live="filtroTipo" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($tipiList as $t)
            <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
          </select>
        </div>

        {{-- Meccanico --}}
        <div class="col-auto">
          <label class="mb-0 font-weight-bold small">Meccanico:</label>
          <select wire:model.live="filtroMeccanico" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($meccanici as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Export --}}
        <div class="col-auto ml-auto d-flex align-items-end">
          <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv mr-1"></i> CSV
          </button>
        </div>

        <div class="col-auto">
          <span wire:loading class="text-muted small"><i class="fas fa-spinner fa-spin mr-1"></i></span>
        </div>
      </div>
    </div>
  </div>

  {{-- Grafico tempi medi lavorazione --}}
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-hourglass-half mr-1"></i> Tempi medi di lavorazione (giorni)</h3>
    </div>
    <div class="card-body">
      <div
        x-data="{
          chart: null,
          dati: @json($graficoTempi),
          init() { this.$nextTick(() => this.creaGrafico()); },
          creaGrafico() {
            if (this.chart) { this.chart.destroy(); }
            if (!(this.dati.labels && this.dati.labels.length)) return;
            const ctx = this.$refs.canvas.getContext('2d');
            this.chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: this.dati.labels || [],
                datasets: [
                  { label: 'Min', data: this.dati.minimi || [], backgroundColor: 'rgba(40,167,69,0.6)' },
                  { label: 'Media', data: this.dati.medie || [], backgroundColor: 'rgba(60,141,188,0.7)' },
                  { label: 'Max', data: this.dati.massimi || [], backgroundColor: 'rgba(220,53,69,0.6)' }
                ]
              },
              options: {
                responsive: true, maintainAspectRatio: false,
                scales: { yAxes: [{ ticks: { beginAtZero: true, callback: v => v + ' gg' } }] }
              }
            });
          }
        }"
        @tempi-chart-aggiornato.window="dati = $event.detail.graficoTempi; creaGrafico()"
      >
        <canvas x-ref="canvas" style="min-height:200px"></canvas>
      </div>
    </div>
  </div>

  {{-- Tabella commesse --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i> Elenco commesse</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th>N°</th>
              <th>Cliente</th>
              <th>Veicolo</th>
              <th>Ingresso</th>
              <th>Consegna</th>
              <th class="text-right">Attesa</th>
              <th>Tipo</th>
              <th>Meccanico</th>
              <th class="text-right">Prev. (€)</th>
              <th class="text-right">Fatt. (€)</th>
              <th>Stato</th>
            </tr>
          </thead>
          <tbody>
            @forelse($commesse as $c)
            <tr>
              <td>
                <a href="{{ route('commesse.show', $c->id) }}">{{ $c->numero }}</a>
              </td>
              <td>{{ trim($c->cliente_nome) ?: '—' }}</td>
              <td><small>{{ trim($c->veicolo_desc) ?: '—' }}</small></td>
              <td>{{ $c->data_ingresso ? \Carbon\Carbon::parse($c->data_ingresso)->format('d/m/Y') : '—' }}</td>
              <td>{{ $c->data_consegna ? \Carbon\Carbon::parse($c->data_consegna)->format('d/m/Y') : '—' }}</td>
              <td class="text-right">{{ $c->giorni_attesa }} gg</td>
              <td>
                <span class="badge badge-secondary">
                  {{ \App\Enums\TipoCommessa::from($c->tipo)->label() }}
                </span>
              </td>
              <td>{{ $c->meccanico_nome ?? '—' }}</td>
              <td class="text-right">{{ number_format($c->totale_preventivato, 2, ',', '.') }}</td>
              <td class="text-right">{{ number_format($c->totale_fatturato, 2, ',', '.') }}</td>
              <td>
                <span class="badge {{ \App\Enums\StatoCommessa::from($c->stato)->badgeClass() }}">
                  {{ \App\Enums\StatoCommessa::from($c->stato)->label() }}
                </span>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-4">Nessuna commessa nel periodo</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($commesse->hasPages())
    <div class="card-footer">
      {{ $commesse->links() }}
    </div>
    @endif
  </div>

</div>
