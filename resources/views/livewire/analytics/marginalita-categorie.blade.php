<div>

  {{-- Filtro periodo --}}
  @include('livewire.analytics._filtro-periodo')

  <div class="row">

    {{-- Grafico torta margine per categoria --}}
    <div class="col-lg-4 mb-3">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Margine per categoria</h3>
        </div>
        <div class="card-body">
          <div
            x-data="{
              chart: null,
              dati: @json($graficoTorta),
              init() { this.$nextTick(() => this.creaGrafico()); },
              creaGrafico() {
                if (this.chart) { this.chart.destroy(); }
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                  type: 'pie',
                  data: {
                    labels: this.dati.labels || [],
                    datasets: [{ data: this.dati.valori || [], backgroundColor: this.dati.colori || [] }]
                  },
                  options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' } }
                });
              }
            }"
            @marginalita-chart-aggiornato.window="dati = $event.detail.graficoTorta; creaGrafico()"
          >
            <canvas x-ref="canvas" style="min-height:250px"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabella per categoria --}}
    <div class="col-lg-8 mb-3">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><i class="fas fa-layer-group mr-1"></i> Marginalità per categoria</h3>
          <button wire:click="esportaCsvCategorie" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv mr-1"></i> CSV
          </button>
        </div>
        <div class="card-body p-0">
          <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>Categoria</th>
                <th class="text-right">Fatturato (€)</th>
                <th class="text-right">Costo (€)</th>
                <th class="text-right">Margine (€)</th>
                <th class="text-right">Margine %</th>
              </tr>
            </thead>
            <tbody>
              @forelse($perCategoria as $row)
              <tr>
                <td><strong>{{ $row['label'] }}</strong></td>
                <td class="text-right">{{ number_format($row['ricavo_totale'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($row['costo_totale'], 2, ',', '.') }}</td>
                <td class="text-right {{ $row['margine_lordo'] >= 0 ? 'text-success' : 'text-danger' }}">
                  <strong>{{ number_format($row['margine_lordo'], 2, ',', '.') }}</strong>
                </td>
                <td class="text-right">
                  <span class="badge {{ $row['percentuale'] >= 40 ? 'badge-success' : ($row['percentuale'] >= 20 ? 'badge-warning' : 'badge-danger') }}">
                    {{ $row['percentuale'] }}%
                  </span>
                </td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted py-4">Nessun dato nel periodo</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Trend mensile 6 mesi --}}
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Trend margine mensile (ultimi 6 mesi)</h3>
    </div>
    <div class="card-body">
      <div
        x-data="{
          chart: null,
          dati: @json($trendMensile),
          colori: ['rgba(60,141,188,0.7)', 'rgba(243,156,18,0.7)', 'rgba(40,167,69,0.7)'],
          init() { this.$nextTick(() => this.creaGrafico()); },
          creaGrafico() {
            if (this.chart) { this.chart.destroy(); }
            const ctx = this.$refs.canvas.getContext('2d');
            const datasets = (this.dati.datasets || []).map((ds, i) => ({
              label: ds.label,
              data: ds.valori,
              backgroundColor: this.colori[i] || 'rgba(128,128,128,0.5)',
              borderColor: this.colori[i] || 'rgba(128,128,128,0.7)',
              borderWidth: 2,
              fill: false,
              type: 'line',
              pointRadius: 4
            }));
            this.chart = new Chart(ctx, {
              type: 'bar',
              data: { labels: this.dati.labels || [], datasets },
              options: { responsive: true, maintainAspectRatio: false, scales: { yAxes: [{ ticks: { beginAtZero: true, callback: v => '€' + v.toLocaleString('it-IT') } }] } }
            });
          }
        }"
        @marginalita-chart-aggiornato.window="dati = $event.detail.trendMensile; creaGrafico()"
      >
        <canvas x-ref="canvas" style="min-height:250px"></canvas>
      </div>
    </div>
  </div>

  {{-- Tabella articoli --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0"><i class="fas fa-boxes mr-1"></i> Marginalità per articolo</h3>
      <button wire:click="esportaCsvArticoli" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv mr-1"></i> CSV
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:400px;overflow-y:auto">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light" style="position:sticky;top:0">
            <tr>
              <th>Articolo</th>
              <th class="text-right">Ricavo (€)</th>
              <th class="text-right">Costo (€)</th>
              <th class="text-right">Margine (€)</th>
              <th class="text-right">Margine %</th>
            </tr>
          </thead>
          <tbody>
            @forelse($perArticoli as $r)
            <tr>
              <td>{{ $r['descrizione'] }}</td>
              <td class="text-right">{{ number_format($r['ricavo'], 2, ',', '.') }}</td>
              <td class="text-right">{{ number_format($r['costo'], 2, ',', '.') }}</td>
              <td class="text-right {{ $r['margine'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($r['margine'], 2, ',', '.') }}
              </td>
              <td class="text-right">
                <span class="badge {{ $r['percentuale'] >= 40 ? 'badge-success' : ($r['percentuale'] >= 20 ? 'badge-warning' : 'badge-danger') }}">
                  {{ $r['percentuale'] }}%
                </span>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Nessun dato nel periodo</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
