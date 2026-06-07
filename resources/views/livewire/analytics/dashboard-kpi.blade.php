<div>

  {{-- Filtro periodo --}}
  <div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
      <div class="row align-items-center">
        <div class="col-auto">
          <label class="mb-0 font-weight-bold">Periodo:</label>
        </div>
        <div class="col-auto">
          <select wire:model.live="periodo" class="form-control form-control-sm">
            <option value="questo_mese">Questo mese</option>
            <option value="mese_scorso">Mese scorso</option>
            <option value="questo_trimestre">Questo trimestre</option>
            <option value="questo_anno">Questo anno</option>
            <option value="personalizzato">Personalizzato</option>
          </select>
        </div>
        @if($periodo === 'personalizzato')
        <div class="col-auto d-flex align-items-center gap-2">
          <input type="date" wire:model.defer="dataDa" class="form-control form-control-sm" style="width:150px">
          <span class="mx-1">—</span>
          <input type="date" wire:model.defer="dataA" class="form-control form-control-sm" style="width:150px">
          <button wire:click="applicaFiltroPersonalizzato" class="btn btn-sm btn-primary ml-2">
            <i class="fas fa-filter"></i> Applica
          </button>
        </div>
        @endif
        <div class="col-auto ml-auto">
          <span wire:loading class="text-muted small"><i class="fas fa-spinner fa-spin mr-1"></i>Aggiornamento...</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Riga KPI --}}
  <div class="row">

    {{-- Box 1: Fatturato --}}
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="small-box bg-info">
        <div class="inner">
          <h3>€ {{ number_format($fatturato, 0, ',', '.') }}</h3>
          <p>
            Fatturato
            @if($deltaFatturato != 0)
            <span class="badge {{ $deltaFatturato >= 0 ? 'badge-success' : 'badge-danger' }} ml-1">
              {{ $deltaFatturato >= 0 ? '+' : '' }}{{ $deltaFatturato }}%
            </span>
            @endif
          </p>
        </div>
        <div class="icon"><i class="fas fa-euro-sign"></i></div>
        {{-- Sparkline --}}
        <div class="px-3 pb-2" style="height:50px">
          <div
            x-data="{
              chart: null,
              init() {
                const ctx = this.$refs.spark.getContext('2d');
                this.chart = new Chart(ctx, {
                  type: 'line',
                  data: {
                    labels: Array.from({length: {{ count($sparkline) }}}, (_, i) => i),
                    datasets: [{ data: @json($sparkline), borderColor: 'rgba(255,255,255,0.8)', borderWidth: 2, pointRadius: 0, fill: false }]
                  },
                  options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ display: false }], yAxes: [{ display: false }] }, tooltips: { enabled: false } }
                });
              }
            }"
          >
            <canvas x-ref="spark" style="max-height:40px"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- Box 2: Ticket medio --}}
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="small-box bg-success">
        <div class="inner">
          <h3>€ {{ number_format($ticketMedio, 0, ',', '.') }}</h3>
          <p>
            Ticket medio
            @if($deltaTicketMedio != 0)
            <span class="badge {{ $deltaTicketMedio >= 0 ? 'badge-light' : 'badge-danger' }} ml-1">
              {{ $deltaTicketMedio >= 0 ? '+' : '' }}{{ $deltaTicketMedio }}%
            </span>
            @endif
          </p>
        </div>
        <div class="icon"><i class="fas fa-receipt"></i></div>
        <div class="small-box-footer">&nbsp;</div>
      </div>
    </div>

    {{-- Box 3: Commesse aperte --}}
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3>{{ $commesseAperte['totale'] ?? 0 }}</h3>
          <p>
            Commesse aperte
            <small class="d-block">
              Mecc. {{ $commesseAperte['meccanica'] ?? 0 }} /
              Carr. {{ $commesseAperte['carrozzeria'] ?? 0 }}
            </small>
          </p>
        </div>
        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
        <a href="{{ route('commesse.index') }}" class="small-box-footer">
          Vedi tutte <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    {{-- Box 4: Efficienza ore --}}
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3>
            {{ $oreEfficienza['efficienza'] ?? 0 }}%
            <small class="text-sm">efficienza</small>
          </h3>
          <p>
            Ore: {{ $oreEfficienza['ore_fatturabili'] ?? 0 }} fatt. /
            {{ $oreEfficienza['ore_lavorate'] ?? 0 }} lav.
          </p>
        </div>
        <div class="icon"><i class="fas fa-stopwatch"></i></div>
        <div class="small-box-footer">&nbsp;</div>
      </div>
    </div>
  </div>

  {{-- Riga grafici --}}
  <div class="row">

    {{-- Grafico fatturato mensile --}}
    <div class="col-lg-8 mb-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Fatturato mensile (ultimi 12 mesi)</h3>
        </div>
        <div class="card-body">
          <div
            x-data="{
              chart: null,
              dati: @json($graficoFatturato),
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
                    datasets: [{
                      label: 'Fatturato (€)',
                      data: this.dati.valori || [],
                      backgroundColor: this.dati.colori || 'rgba(60,141,188,0.7)',
                      borderWidth: 0
                    }]
                  },
                  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    scales: {
                      yAxes: [{ ticks: { callback: v => '€' + v.toLocaleString('it-IT') } }]
                    },
                    tooltips: {
                      callbacks: {
                        label: (item) => '€ ' + parseFloat(item.yLabel).toLocaleString('it-IT', {minimumFractionDigits: 2})
                      }
                    }
                  }
                });
              }
            }"
            @dashboard-charts-aggiornati.window="dati = $event.detail.graficoFatturato; creaGrafico()"
          >
            <canvas x-ref="canvas" style="min-height:250px"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- Grafico distribuzione commesse --}}
    <div class="col-lg-4 mb-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Distribuzione commesse</h3>
        </div>
        <div class="card-body">
          <div
            x-data="{
              chart: null,
              dati: @json($graficoCommesse),
              init() {
                this.$nextTick(() => this.creaGrafico());
              },
              creaGrafico() {
                if (this.chart) { this.chart.destroy(); }
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                  type: 'doughnut',
                  data: {
                    labels: this.dati.labels || [],
                    datasets: [{
                      data: this.dati.valori || [],
                      backgroundColor: this.dati.colori || []
                    }]
                  },
                  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' }
                  }
                });
              }
            }"
            @dashboard-charts-aggiornati.window="dati = $event.detail.graficoCommesse; creaGrafico()"
          >
            <canvas x-ref="canvas" style="min-height:250px"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Riga tabelle --}}
  <div class="row">

    {{-- Top 10 clienti --}}
    <div class="col-lg-7 mb-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-trophy mr-1"></i> Top 10 clienti per fatturato</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th class="text-right">Fatture</th>
                <th class="text-right">Fatturato</th>
                <th class="text-right">Ticket medio</th>
                <th>Ultima visita</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topClienti as $i => $c)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                  <a href="{{ route('clienti.index') }}">{{ trim($c['nome_completo']) ?: '—' }}</a>
                </td>
                <td class="text-right">{{ $c['num_fatture'] }}</td>
                <td class="text-right">€ {{ number_format($c['fatturato'], 2, ',', '.') }}</td>
                <td class="text-right">€ {{ number_format($c['ticket_medio'], 2, ',', '.') }}</td>
                <td>{{ $c['ultima_visita'] ? \Carbon\Carbon::parse($c['ultima_visita'])->format('d/m/Y') : '—' }}</td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted py-3">Nessun dato nel periodo</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Articoli più consumati --}}
    <div class="col-lg-5 mb-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Articoli più consumati</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Articolo</th>
                <th>Categoria</th>
                <th class="text-right">Qtà</th>
                <th class="text-right">Valore</th>
              </tr>
            </thead>
            <tbody>
              @forelse($articoliConsumati as $a)
              <tr>
                <td>{{ $a['descrizione'] }}</td>
                <td><small>{{ $a['categoria'] }}</small></td>
                <td class="text-right">{{ number_format($a['quantita_scaricata'], 2, ',', '') }}</td>
                <td class="text-right">€ {{ number_format($a['valore_scaricato'], 2, ',', '.') }}</td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center text-muted py-3">Nessun dato</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Widget operativi (real-time) --}}
  <div class="row">
    <div class="col-md-4 mb-3">
      <div class="info-box">
        <span class="info-box-icon {{ ($widgetOperativi['commesse_attesa'] ?? 0) > 0 ? 'bg-warning' : 'bg-secondary' }}">
          <i class="fas fa-clock"></i>
        </span>
        <div class="info-box-content">
          <span class="info-box-text">Commesse in attesa &gt;24h</span>
          <span class="info-box-number">{{ $widgetOperativi['commesse_attesa'] ?? 0 }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="info-box">
        <span class="info-box-icon bg-info">
          <i class="fas fa-calendar-day"></i>
        </span>
        <div class="info-box-content">
          <span class="info-box-text">Appuntamenti oggi</span>
          <span class="info-box-number">{{ $widgetOperativi['appuntamenti_oggi'] ?? 0 }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="info-box">
        <span class="info-box-icon {{ ($widgetOperativi['sotto_scorta'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }}">
          <i class="fas fa-exclamation-triangle"></i>
        </span>
        <div class="info-box-content">
          <span class="info-box-text">Articoli sotto scorta</span>
          <span class="info-box-number">{{ $widgetOperativi['sotto_scorta'] ?? 0 }}</span>
          @if(($widgetOperativi['sotto_scorta'] ?? 0) > 0)
          <span class="progress-description">
            <a href="{{ route('magazzino.articoli') }}">Vai al magazzino</a>
          </span>
          @endif
        </div>
      </div>
    </div>
  </div>

</div>
