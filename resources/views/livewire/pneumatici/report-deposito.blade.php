<div>
  {{-- KPI box --}}
  <div class="row mb-3">
    @foreach($countPerStagione as $val => $count)
    @php $s = \App\Enums\StagionePneumatico::from($val); @endphp
    <div class="col-md-4">
      <div class="small-box bg-{{ $s->value === 'invernale' ? 'info' : ($s->value === 'estivo' ? 'warning' : 'secondary') }}">
        <div class="inner">
          <h3>{{ $count }}</h3>
          <p>{{ $s->label() }} in deposito</p>
        </div>
        <div class="icon"><i class="fas fa-snowflake"></i></div>
      </div>
    </div>
    @endforeach
  </div>

  @if($inAttesa > 0)
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <strong>{{ $inAttesa }}</strong> set in deposito da oltre 180 giorni — contattare i clienti per ritiro o smaltimento.
  </div>
  @endif

  {{-- Tab --}}
  <ul class="nav nav-tabs">
    <li class="nav-item">
      <a href="#" wire:click.prevent="$set('tab', 'corrente')"
         class="nav-link {{ $tab === 'corrente' ? 'active' : '' }}">
        <i class="fas fa-boxes mr-1"></i>Situazione corrente
      </a>
    </li>
    <li class="nav-item">
      <a href="#" wire:click.prevent="$set('tab', 'mappa')"
         class="nav-link {{ $tab === 'mappa' ? 'active' : '' }}">
        <i class="fas fa-th mr-1"></i>Mappa scaffali
      </a>
    </li>
    <li class="nav-item">
      <a href="#" wire:click.prevent="$set('tab', 'smaltiti')"
         class="nav-link {{ $tab === 'smaltiti' ? 'active' : '' }}">
        <i class="fas fa-recycle mr-1"></i>Storico smaltimenti
      </a>
    </li>
  </ul>

  <div class="border border-top-0 p-3 bg-white">

    @if($tab === 'corrente')
    {{-- Filtri --}}
    <div class="row mb-3">
      <div class="col-md-4">
        <select wire:model.live="filtroStagione" class="form-control form-control-sm">
          <option value="">Tutte le stagioni</option>
          @foreach(\App\Enums\StagionePneumatico::cases() as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <input wire:model.live.debounce.300ms="filtroUbicazione" type="text"
               class="form-control form-control-sm" placeholder="Filtra ubicazione...">
      </div>
      <div class="col-md-4 text-right">
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-file-csv mr-1"></i>Export CSV
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="thead-light">
          <tr>
            <th>Cliente</th>
            <th>Targa</th>
            <th>Stagione</th>
            <th>Misura</th>
            <th>Ubicazione</th>
            <th>Data deposito</th>
            <th>Gg in deposito</th>
            <th>Usura</th>
          </tr>
        </thead>
        <tbody>
          @forelse($correnti as $p)
          @php $ult = $p->movimenti->first(); @endphp
          <tr class="{{ $ult && $ult->data_azione->diffInDays(now()) > 180 ? 'table-warning' : '' }}">
            <td>{{ $p->cliente?->nome_completo ?? '—' }}</td>
            <td>
              @if($p->veicolo)
                <a href="{{ route('veicoli.show', $p->veicolo_id) }}">{{ $p->veicolo->targa }}</a>
              @endif
            </td>
            <td><span class="badge {{ $p->stagione->badgeClass() }}">{{ $p->stagione->label() }}</span></td>
            <td>{{ $p->misura }}</td>
            <td><small>{{ $ult?->ubicazione ?? '—' }}</small></td>
            <td>{{ $ult ? $ult->data_azione->format('d/m/Y') : '—' }}</td>
            <td>
              @if($ult)
                {{ $ult->data_azione->diffInDays(now()) }} gg
              @endif
            </td>
            <td>
              @if($ult && $ult->usura_percentuale !== null)
                <div class="progress" style="height:8px;min-width:60px">
                  <div class="progress-bar {{ $ult->usura_percentuale > 70 ? 'bg-danger' : ($ult->usura_percentuale > 40 ? 'bg-warning' : 'bg-success') }}"
                       style="width:{{ $ult->usura_percentuale }}%"></div>
                </div>
                <small>{{ $ult->usura_percentuale }}%</small>
              @else —
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="8" class="text-center text-muted py-3">Nessun pneumatico in deposito.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $correnti->links() }}</div>
    @endif

    @if($tab === 'mappa')
    <h5 class="mb-3">Mappa occupazione scaffali</h5>
    @if(empty($mappaScaffali))
      <p class="text-muted">Nessuna ubicazione strutturata rilevata. Le ubicazioni devono seguire il pattern "Scaffale A3" o "A3".</p>
    @else
    <div style="overflow-x:auto">
      <svg id="mappa-scaffali" xmlns="http://www.w3.org/2000/svg">
        @php
          $cellW = 80; $cellH = 40; $pad = 20;
          $cols  = array_keys($mappaScaffali);
          $maxRow = 0;
          foreach ($mappaScaffali as $rows) {
              $maxRow = max($maxRow, empty($rows) ? 0 : max(array_keys($rows)));
          }
          $svgW = count($cols) * ($cellW + 5) + $pad * 2;
          $svgH = $maxRow * ($cellH + 5) + $pad * 2 + 20;
        @endphp
        @foreach($cols as $ci => $col)
          {{-- Header colonna --}}
          <text x="{{ $pad + $ci * ($cellW + 5) + $cellW/2 }}" y="{{ $pad - 5 }}"
                text-anchor="middle" font-size="13" font-weight="bold" fill="#333">{{ $col }}</text>
          @for($r = 1; $r <= $maxRow; $r++)
            @php
              $x    = $pad + $ci * ($cellW + 5);
              $y    = $pad + ($r - 1) * ($cellH + 5);
              $cella = $mappaScaffali[$col][$r] ?? null;
            @endphp
            @if($cella && $cella['occupato'])
              <rect x="{{ $x }}" y="{{ $y }}" width="{{ $cellW }}" height="{{ $cellH }}"
                    fill="#dc3545" rx="4" ry="4">
                <title>{{ $cella['info']['targa'] ?? '' }} — {{ $cella['info']['cliente'] ?? '' }}</title>
              </rect>
              <text x="{{ $x + $cellW/2 }}" y="{{ $y + $cellH/2 + 4 }}" text-anchor="middle"
                    font-size="10" fill="white">{{ $cella['info']['targa'] ?? $col.$r }}</text>
            @else
              <rect x="{{ $x }}" y="{{ $y }}" width="{{ $cellW }}" height="{{ $cellH }}"
                    fill="#28a745" rx="4" ry="4"/>
              <text x="{{ $x + $cellW/2 }}" y="{{ $y + $cellH/2 + 4 }}" text-anchor="middle"
                    font-size="10" fill="white">{{ $col }}{{ $r }}</text>
            @endif
          @endfor
        @endforeach
        {{-- Legenda --}}
        <rect x="{{ $pad }}" y="{{ $svgH - 18 }}" width="12" height="12" fill="#dc3545" rx="2"/>
        <text x="{{ $pad + 16 }}" y="{{ $svgH - 8 }}" font-size="11" fill="#333">Occupato</text>
        <rect x="{{ $pad + 90 }}" y="{{ $svgH - 18 }}" width="12" height="12" fill="#28a745" rx="2"/>
        <text x="{{ $pad + 106 }}" y="{{ $svgH - 8 }}" font-size="11" fill="#333">Libero</text>
      </svg>
    </div>
    @endif
    @endif

    @if($tab === 'smaltiti')
    <p class="text-muted small mb-2">
      Pneumatici fuori uso smaltiti nell'anno {{ now()->year }}. Conservare la documentazione PFU (D.Lgs. 152/2006).
    </p>
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead class="thead-light">
          <tr>
            <th>Cliente</th>
            <th>Targa</th>
            <th>Stagione / Misura</th>
            <th>Data smaltimento</th>
          </tr>
        </thead>
        <tbody>
          @forelse($smaltiti as $p)
          @php $ult = $p->movimenti->where('azione.value', 'smaltimento')->first() ?? $p->movimenti->last(); @endphp
          <tr>
            <td>{{ $p->cliente?->nome_completo ?? '—' }}</td>
            <td>{{ $p->veicolo?->targa ?? '—' }}</td>
            <td>
              <span class="badge {{ $p->stagione->badgeClass() }}">{{ $p->stagione->label() }}</span>
              {{ $p->misura }}
            </td>
            <td>{{ $p->updated_at->format('d/m/Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-muted py-3">Nessun smaltimento registrato quest'anno.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $smaltiti->links() }}</div>
    @endif

  </div>
</div>
