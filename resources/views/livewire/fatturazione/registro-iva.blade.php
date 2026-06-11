<div>
  <div class="card card-outline card-secondary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book mr-2"></i>Registro IVA</h3>
      <div class="card-tools">
        <a href="{{ route('contabilita.riepilogo') }}" class="btn btn-sm btn-outline-primary mr-1">
          <i class="fas fa-calculator mr-1"></i> Export commercialista
        </a>
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-file-csv mr-1"></i> Esporta CSV
        </button>
      </div>
    </div>

    {{-- Tab selezione --}}
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs" id="tabRegistroIva">
        <li class="nav-item">
          <a class="nav-link {{ $tab === 'vendite' ? 'active' : '' }}"
             wire:click="$set('tab','vendite')" href="#" role="tab">
            <i class="fas fa-arrow-up text-success mr-1"></i> Vendite
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab === 'acquisti' ? 'active' : '' }}"
             wire:click="$set('tab','acquisti')" href="#" role="tab">
            <i class="fas fa-arrow-down text-danger mr-1"></i> Acquisti
          </a>
        </li>
      </ul>
    </div>

    <div class="card-header bg-light py-2">
      <div class="alert alert-warning py-1 mb-2 small">
        <i class="fas fa-info-circle mr-1"></i>
        Questo registro è un <strong>prospetto operativo</strong> e non ha valore fiscale.
        Il libro IVA ufficiale è di competenza del commercialista.
      </div>
      <div class="row g-2">
        <div class="col-md-2">
          <select wire:model.live="filtroAnno" class="form-control form-control-sm">
            @foreach($anni as $anno)
              <option value="{{ $anno }}">{{ $anno }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroMese" class="form-control form-control-sm">
            <option value="">Tutti i mesi</option>
            @foreach(range(1,12) as $m)
              <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">
                {{ \Carbon\Carbon::create(null,$m)->translatedFormat('F') }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Data</th>
              <th>Numero</th>
              <th>{{ $tab === 'vendite' ? 'Cliente' : 'Fornitore' }}</th>
              <th>P.IVA / C.F.</th>
              <th class="text-right">Aliquota %</th>
              <th>Natura</th>
              <th class="text-right">Imponibile</th>
              <th class="text-right">IVA</th>
              <th class="text-right">Totale</th>
            </tr>
          </thead>
          <tbody>
            @forelse($righe as $r)
            <tr>
              <td>{{ $r->data_registrazione->format('d/m/Y') }}</td>
              <td>{{ $r->numero_documento }}</td>
              <td>{{ $r->cliente_fornitore }}</td>
              <td class="small text-muted">{{ $r->partita_iva ?? $r->codice_fiscale ?? '—' }}</td>
              <td class="text-right">{{ number_format((float)$r->aliquota_iva, 2, ',', '.') }}%</td>
              <td>{{ $r->natura_iva ?? '—' }}</td>
              <td class="text-right">€ {{ number_format((float)$r->imponibile, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$r->iva, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$r->totale, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Nessun movimento nel periodo selezionato.</td></tr>
            @endforelse
          </tbody>
          @if($righe->isNotEmpty())
          <tfoot class="font-weight-bold table-secondary">
            <tr>
              <td colspan="6" class="text-right">Totali periodo</td>
              <td class="text-right">€ {{ number_format($totali['imponibile'], 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format($totali['iva'], 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format($totali['totale'], 2, ',', '.') }}</td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
