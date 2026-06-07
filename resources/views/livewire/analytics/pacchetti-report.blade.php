<div>
  <!-- Top Pacchetti -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Top 10 Pacchetti per Utilizzo</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>#</th>
            <th>Pacchetto</th>
            <th class="text-center">Utilizzi</th>
            <th class="text-right">Ricavo generato (imponibile)</th>
            <th class="text-center">Stato</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topPacchetti as $i => $p)
          <tr>
            <td class="text-muted">{{ $i + 1 }}</td>
            <td><strong>{{ $p['nome'] }}</strong></td>
            <td class="text-center">
              <span class="badge badge-secondary">{{ $p['utilizzi'] }}×</span>
            </td>
            <td class="text-right font-weight-bold">€ {{ number_format($p['ricavo'], 2, ',', '.') }}</td>
            <td class="text-center">
              <span class="badge {{ $p['attivo'] ? 'badge-success' : 'badge-secondary' }}">
                {{ $p['attivo'] ? 'Attivo' : 'Archiviato' }}
              </span>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-4">Nessun pacchetto ancora utilizzato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tariffe fuori listino -->
  <div class="card mt-4">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
        Tariffe Fuori Listino
        <small class="text-muted">(scostamento &gt; 15% dal prezzo di listino)</small>
      </h3>
    </div>
    <div class="card-body p-0">
      @if($tariffeFuoriListino->isEmpty())
      <div class="text-center text-muted py-4">
        <i class="fas fa-check-circle fa-2x mb-2 text-success d-block"></i>
        Nessuna riga con scostamento significativo dal tariffario.
      </div>
      @else
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Commessa</th>
            <th>Cliente</th>
            <th>Tariffa</th>
            <th>Descrizione</th>
            <th class="text-right">Listino €</th>
            <th class="text-right">Applicato €</th>
            <th class="text-right">Scostamento</th>
          </tr>
        </thead>
        <tbody>
          @foreach($tariffeFuoriListino as $r)
          <tr>
            <td><code>{{ $r['commessa_numero'] }}</code></td>
            <td>{{ $r['cliente'] }}</td>
            <td><code>{{ $r['codice_tariffa'] }}</code></td>
            <td>{{ Str::limit($r['descrizione'], 50) }}</td>
            <td class="text-right">€ {{ number_format($r['prezzo_listino'], 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($r['prezzo_applicato'], 2, ',', '.') }}</td>
            <td class="text-right">
              <span class="badge {{ $r['scostamento_pct'] > 0 ? 'badge-success' : 'badge-danger' }}">
                {{ $r['scostamento_pct'] > 0 ? '+' : '' }}{{ $r['scostamento_pct'] }}%
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    </div>
  </div>
</div>
