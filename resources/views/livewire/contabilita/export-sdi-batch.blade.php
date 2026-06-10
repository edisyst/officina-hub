<div>
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('error') }}
    </div>
  @endif

  <div class="alert alert-info">
    <i class="fas fa-info-circle mr-1"></i>
    Questo archivio può essere caricato sul portale <strong>Fatture e Corrispettivi</strong>
    dell'Agenzia delle Entrate in modalità upload multiplo.
  </div>

  <div class="card card-outline card-secondary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-archive mr-2"></i>Export XML SDI Batch</h3>
      <div class="card-tools">
        <button wire:click="generaZip"
          @disabled(empty($selezionati))
          class="btn btn-sm btn-success">
          <span wire:loading.remove wire:target="generaZip">
            <i class="fas fa-file-archive mr-1"></i> Genera archivio ZIP ({{ count($selezionati) }})
          </span>
          <span wire:loading wire:target="generaZip">
            <i class="fas fa-spinner fa-spin mr-1"></i> Generazione...
          </span>
        </button>
      </div>
    </div>

    {{-- Filtri --}}
    <div class="card-header bg-light py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="small">Dal</label>
          <input type="date" wire:model.live="filtroDal" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="small">Al</label>
          <input type="date" wire:model.live="filtroAl" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
          <label class="small">Stato documento</label>
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti</option>
            <option value="emessa">Emessa</option>
            <option value="inviata_sdi">Inviata SdI</option>
            <option value="accettata_sdi">Accettata SdI</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:40px">
                <input type="checkbox" wire:model.live="tuttiSelezionati" wire:change="toggleTutti">
              </th>
              <th>Numero</th>
              <th>Data</th>
              <th>Cliente</th>
              <th>Stato</th>
              <th class="text-right">Totale</th>
            </tr>
          </thead>
          <tbody>
            @forelse($documenti as $doc)
            <tr>
              <td>
                <input type="checkbox" wire:model.live="selezionati" value="{{ $doc->id }}">
              </td>
              <td>
                <a href="{{ route('fatturazione.documenti.show', $doc->id) }}" target="_blank">
                  {{ $doc->numero }}
                </a>
              </td>
              <td>{{ $doc->data_emissione?->format('d/m/Y') }}</td>
              <td>{{ $doc->cliente?->nome_completo }}</td>
              <td>
                <span class="badge {{ $doc->stato->badgeClass() }}">{{ $doc->stato->label() }}</span>
              </td>
              <td class="text-right">€ {{ number_format((float)$doc->totale_documento, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Nessun documento nel periodo selezionato.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($documenti->isNotEmpty())
    <div class="card-footer text-muted small">
      {{ $documenti->count() }} documento/i trovati — {{ count($selezionati) }} selezionati
    </div>
    @endif
  </div>
</div>
