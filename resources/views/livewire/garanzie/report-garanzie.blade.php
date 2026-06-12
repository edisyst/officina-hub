<div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-shield-alt mr-2"></i>Report Garanzie</h4>
  </div>

  <!-- Filtri -->
  <div class="card mb-4">
    <div class="card-body py-2">
      <div class="row align-items-end">
        <div class="col-md-3">
          <label class="small text-muted">Dal</label>
          <input wire:model.live="dal" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
          <label class="small text-muted">Al</label>
          <input wire:model.live="al" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="small text-muted">Casa Madre</label>
          <select wire:model.live="casaMadreId" class="form-control form-control-sm">
            <option value="">— Tutte —</option>
            @foreach($caseMadri as $cm)
            <option value="{{ $cm->id }}">{{ $cm->ragione_sociale }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary w-100">
            <i class="fas fa-download mr-1"></i> CSV
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-info"><i class="fas fa-wrench"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Interventi in garanzia</span>
          <span class="info-box-number">{{ $dati['count_interventi'] }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-warning"><i class="fas fa-euro-sign"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Valore coperto da garanzie</span>
          <span class="info-box-number">€ {{ number_format($dati['valore_cliente_perso'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-success"><i class="fas fa-file-invoice-dollar"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Da rimborsare (case madri)</span>
          <span class="info-box-number">€ {{ number_format($dati['totale_da_rimborsare'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-primary"><i class="fas fa-check-circle"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Rimborsi incassati</span>
          <span class="info-box-number">€ {{ number_format($dati['totale_rimborsato'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Per casa madre -->
  @if(count($dati['per_casa_madre']) > 0)
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Dettaglio per Casa Madre</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Casa Madre</th>
            <th class="text-right">Rimborsi attesi</th>
            <th class="text-right">Incassati</th>
            <th class="text-right">Delta</th>
            <th class="text-center">Fatture emesse</th>
            <th class="text-center">Pagate</th>
            <th class="text-center">Scadute</th>
          </tr>
        </thead>
        <tbody>
          @foreach($dati['per_casa_madre'] as $row)
          <tr>
            <td><strong>{{ $row['ragione_sociale'] }}</strong></td>
            <td class="text-right">€ {{ number_format($row['rimborsi_attesi'], 2, ',', '.') }}</td>
            <td class="text-right">€ {{ number_format($row['incassati'], 2, ',', '.') }}</td>
            <td class="text-right {{ $row['delta'] > 0 ? 'text-warning' : 'text-success' }}">
              € {{ number_format($row['delta'], 2, ',', '.') }}
            </td>
            <td class="text-center">{{ $row['fatture_emesse'] }}</td>
            <td class="text-center">
              <span class="badge badge-success">{{ $row['fatture_pagate'] }}</span>
            </td>
            <td class="text-center">
              @if($row['fatture_scadute'] > 0)
              <span class="badge badge-danger">{{ $row['fatture_scadute'] }}</span>
              @else
              <span class="badge badge-secondary">0</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @else
  <p class="text-muted text-center py-4">Nessuna fattura garanzia emessa nel periodo selezionato.</p>
  @endif
</div>
