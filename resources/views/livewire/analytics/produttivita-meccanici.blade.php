<div>

  {{-- Filtro periodo --}}
  @include('livewire.analytics._filtro-periodo')

  {{-- Grafico barre ore --}}
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i> Ore lavorate vs fatturate</h3>
      <div>
        <button
          wire:click="esportaCsv"
          class="btn btn-sm btn-outline-secondary mr-1"
          title="Esporta CSV"
        >
          <i class="fas fa-file-csv mr-1"></i> CSV
        </button>
        <button
          onclick="esportaPdfConGrafico()"
          class="btn btn-sm btn-outline-secondary"
          title="Esporta PDF"
        >
          <i class="fas fa-file-pdf mr-1"></i> PDF
        </button>
      </div>
    </div>
    <div class="card-body">
      <div
        x-data="{
          chart: null,
          dati: @json($grafico),
          init() {
            this.$nextTick(() => this.creaGrafico());
          },
          creaGrafico() {
            if (this.chart) { this.chart.destroy(); }
            const ctx = this.$refs.canvas.getContext('2d');
            this.chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: this.dati.labels || [],
                datasets: [
                  { label: 'Ore lavorate', data: this.dati.lavorate || [], backgroundColor: 'rgba(60,141,188,0.7)' },
                  { label: 'Ore fatturate', data: this.dati.fatturate || [], backgroundColor: 'rgba(40,167,69,0.7)' }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
              }
            });
          },
          getBase64() {
            return this.chart ? this.chart.toBase64Image() : '';
          }
        }"
        id="chart-produttivita"
        @meccanici-chart-aggiornato.window="dati = $event.detail.grafico; creaGrafico()"
      >
        <canvas x-ref="canvas" style="min-height:260px"></canvas>
      </div>
    </div>
  </div>

  {{-- Tabella meccanici --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Dettaglio produttività</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th>Meccanico</th>
              <th class="text-right">Ore lavorate</th>
              <th class="text-right">Ore fatturate</th>
              <th class="text-right">Efficienza %</th>
              <th class="text-right">Ricavo (€)</th>
              <th class="text-right">Costo (€)</th>
              <th class="text-right">Margine (€)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($datiMeccanici as $m)
            <tr>
              <td><strong>{{ $m['nome'] }}</strong></td>
              <td class="text-right">{{ number_format($m['ore_lavorate'], 1, ',', '') }}</td>
              <td class="text-right">{{ number_format($m['ore_fatturate'], 1, ',', '') }}</td>
              <td class="text-right">
                <span class="badge {{ $m['efficienza'] >= 80 ? 'badge-success' : ($m['efficienza'] >= 60 ? 'badge-warning' : 'badge-danger') }}">
                  {{ $m['efficienza'] }}%
                </span>
              </td>
              <td class="text-right">{{ number_format($m['ricavo_generato'], 2, ',', '.') }}</td>
              <td class="text-right">{{ number_format($m['costo'], 2, ',', '.') }}</td>
              <td class="text-right {{ $m['margine'] >= 0 ? 'text-success' : 'text-danger' }}">
                <strong>{{ number_format($m['margine'], 2, ',', '.') }}</strong>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Nessun dato nel periodo selezionato
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Form nascosto per PDF (il grafico viene catturato in base64 via JS) --}}
  <form id="form-pdf-meccanici" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="grafico_png" id="grafico-png-input">
  </form>

  @push('scripts')
  <script>
    function esportaPdfConGrafico() {
      const alpine = document.querySelector('#chart-produttivita').__x;
      const base64 = alpine.$data.getBase64();
      @this.set('graficoPng', base64);
      setTimeout(() => @this.call('esportaPdf'), 300);
    }
  </script>
  @endpush

</div>
