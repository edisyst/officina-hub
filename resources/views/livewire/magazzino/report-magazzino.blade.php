<div>
  <!-- Tab navigation -->
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'inventario' ? 'active' : '' }}"
        wire:click="$set('tabAttiva','inventario')" href="#">
        <i class="fas fa-warehouse mr-1"></i> Inventario
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'movimenti' ? 'active' : '' }}"
        wire:click="$set('tabAttiva','movimenti')" href="#">
        <i class="fas fa-exchange-alt mr-1"></i> Movimenti per periodo
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'consumi' ? 'active' : '' }}"
        wire:click="$set('tabAttiva','consumi')" href="#">
        <i class="fas fa-chart-bar mr-1"></i> Top consumi
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'rotazione' ? 'active' : '' }}"
        wire:click="$set('tabAttiva','rotazione')" href="#">
        <i class="fas fa-sync-alt mr-1"></i> Rotazione
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'categorie' ? 'active' : '' }}"
        wire:click="$set('tabAttiva','categorie')" href="#">
        <i class="fas fa-tags mr-1"></i> Valore per categoria
      </a>
    </li>
  </ul>

  <!-- Inventario corrente -->
  @if($tabAttiva === 'inventario')
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Codice</th>
            <th>Descrizione</th>
            <th>Categoria</th>
            <th class="text-right">Giacenza</th>
            <th class="text-right">Valore acquisto</th>
            <th class="text-right">Valore vendita</th>
          </tr>
        </thead>
        <tbody>
          @foreach($inventario as $art)
          <tr>
            <td>{{ $art->codice }}</td>
            <td>{{ $art->descrizione }}</td>
            <td><small>{{ $art->categoria?->nome ?? '—' }}</small></td>
            <td class="text-right {{ $art->isSottoScorta() ? 'text-danger font-weight-bold' : '' }}">
              {{ $art->giacenza_attuale }} {{ $art->unita_misura->label() }}
            </td>
            <td class="text-right">€ {{ number_format($art->valore_acquisto, 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($art->valore_vendita, 2, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-light font-weight-bold">
          <tr>
            <td colspan="4" class="text-right">Totali</td>
            <td class="text-right">€ {{ number_format($totaleAcquisto, 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($totaleVendita, 2, ',', '.') }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  @endif

  <!-- Movimenti per periodo -->
  @if($tabAttiva === 'movimenti')
  <div class="card card-body mb-3">
    <div class="row align-items-end">
      <div class="col-md-3">
        <label class="mb-1 small">Da</label>
        <input wire:model.live="dataDa" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="mb-1 small">A</label>
        <input wire:model.live="dataA" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="mb-1 small">Tipo movimento</label>
        <select wire:model.live="filtroTipoMovimento" class="form-control form-control-sm">
          <option value="">Tutti</option>
          @foreach($tipiMovimento as $t)
          <option value="{{ $t->value }}">{{ $t->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary" wire:loading.attr="disabled">
          <span wire:loading wire:target="esportaCsv" class="spinner-border spinner-border-sm mr-1"></span>
          <i class="fas fa-download mr-1"></i> Esporta CSV
        </button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Data</th>
            <th>Articolo</th>
            <th>Tipo</th>
            <th class="text-right">Qnt.</th>
            <th class="text-right">Giacenza prec.</th>
            <th class="text-right">Giacenza succ.</th>
            <th>Commessa</th>
            <th>Utente</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movimentiPeriodo as $mov)
          <tr>
            <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
            <td>
              <a href="{{ route('magazzino.articoli.show', $mov->articolo_id) }}">
                {{ $mov->articolo?->codice }} — {{ $mov->articolo?->descrizione }}
              </a>
            </td>
            <td><span class="badge {{ $mov->tipo->badgeClass() }}">{{ $mov->tipo->label() }}</span></td>
            <td class="text-right">{{ $mov->quantita }}</td>
            <td class="text-right text-muted">{{ $mov->giacenza_precedente }}</td>
            <td class="text-right">{{ $mov->giacenza_successiva }}</td>
            <td>
              @if($mov->commessa)
              <a href="{{ route('commesse.show', $mov->commessa->id) }}">{{ $mov->commessa->numero }}</a>
              @else — @endif
            </td>
            <td><small>{{ $mov->user?->name ?? '—' }}</small></td>
          </tr>
          @empty
          <tr><td colspan="8" class="text-center text-muted py-3">Nessun movimento nel periodo selezionato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($movimentiPeriodo->hasPages())
    <div class="card-footer">{{ $movimentiPeriodo->links() }}</div>
    @endif
  </div>
  @endif

  <!-- Top consumi -->
  @if($tabAttiva === 'consumi')
  <div class="card">
    <div class="card-header">
      <h6 class="mb-0">Top 10 articoli più scaricati nell'ultimo mese</h6>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Articolo</th>
            <th class="text-right">Qnt. scaricata</th>
            <th class="text-right">Valore scaricato</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topConsumi as $i => $consumo)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>
              @if($consumo->articolo)
              <a href="{{ route('magazzino.articoli.show', $consumo->articolo_id) }}">
                {{ $consumo->articolo->codice }} — {{ $consumo->articolo->descrizione }}
              </a>
              @else — @endif
            </td>
            <td class="text-right font-weight-bold">{{ $consumo->totale_scaricato }}</td>
            <td class="text-right">
              @if($consumo->articolo)
              € {{ number_format($consumo->totale_scaricato * (float)$consumo->articolo->prezzo_acquisto, 2, ',', '.') }}
              @else — @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-muted py-3">Nessun scarico nell'ultimo mese.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif

  <!-- Rotazione inventario -->
  @if($tabAttiva === 'rotazione')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-sync-alt mr-1"></i> Rotazione inventario (ultimi 12 mesi)</h3>
      <div class="card-tools">
        <span class="badge badge-success mr-1">Alta &gt;12/anno</span>
        <span class="badge badge-info mr-1">Media 4-12</span>
        <span class="badge badge-warning mr-1">Bassa &lt;4</span>
        <span class="badge badge-danger">Ferma &gt;90gg</span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:500px;overflow-y:auto">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light" style="position:sticky;top:0">
            <tr>
              <th>Articolo</th>
              <th>Categoria</th>
              <th class="text-right">Giacenza</th>
              <th class="text-right">Scaricato/anno</th>
              <th class="text-right">Indice rotazione</th>
              <th>Classe</th>
              <th>Ult. movimento</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rotazione as $art)
            @php
              $badge = match($art->classe_rotazione) {
                'alta'  => 'badge-success',
                'media' => 'badge-info',
                'bassa' => 'badge-warning',
                'ferma' => 'badge-danger',
              };
            @endphp
            <tr>
              <td>
                <a href="{{ route('magazzino.articoli.show', $art->id) }}">
                  {{ $art->codice }} — {{ $art->descrizione }}
                </a>
              </td>
              <td><small>{{ $art->categoria?->nome ?? '—' }}</small></td>
              <td class="text-right">{{ $art->giacenza_attuale }}</td>
              <td class="text-right">{{ $art->indice_rotazione > 0 ? number_format($art->indice_rotazione * $art->giacenza_attuale, 1, ',', '') : '0' }}</td>
              <td class="text-right"><strong>{{ $art->indice_rotazione }}</strong></td>
              <td>
                <span class="badge {{ $badge }}">
                  {{ ucfirst($art->classe_rotazione) }}
                </span>
              </td>
              <td>
                @if($art->giorni_senza_mov !== null)
                  {{ $art->giorni_senza_mov }} gg fa
                @else
                  <span class="text-muted">Mai</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Nessun articolo in magazzino.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <!-- Valore per categoria -->
  @if($tabAttiva === 'categorie')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Valore magazzino per categoria</h3>
    </div>
    <div class="card-body">
      <div
        x-data="{
          chart: null,
          dati: @json($graficoCategorie),
          init() {
            this.$nextTick(() => this.creaGrafico());
          },
          creaGrafico() {
            if (this.chart) { this.chart.destroy(); }
            if (!(this.dati.labels && this.dati.labels.length)) return;
            const ctx = this.$refs.canvas.getContext('2d');
            const colori = this.dati.labels.map((_, i) => {
              const palette = ['rgba(60,141,188,0.8)','rgba(243,156,18,0.8)','rgba(40,167,69,0.8)','rgba(220,53,69,0.8)','rgba(108,117,125,0.8)','rgba(32,201,151,0.8)'];
              return palette[i % palette.length];
            });
            this.chart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: this.dati.labels,
                datasets: [{
                  label: 'Valore a prezzo acquisto (€)',
                  data: this.dati.valori,
                  backgroundColor: colori
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                  yAxes: [{ ticks: { beginAtZero: true, callback: v => '€' + v.toLocaleString('it-IT') } }],
                  xAxes: [{ ticks: { maxRotation: 45 } }]
                }
              }
            });
          }
        }"
      >
        <canvas x-ref="canvas" style="min-height:320px"></canvas>
      </div>
    </div>
  </div>
  @endif

</div>
