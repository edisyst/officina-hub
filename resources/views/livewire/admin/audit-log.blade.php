<div>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title"><i class="fas fa-history mr-1"></i> Audit Log</h3>
      <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv mr-1"></i> Esporta CSV
      </button>
    </div>

    <div class="card-body border-bottom pb-3">
      <div class="row g-2">
        <div class="col-md-3">
          <input type="text" wire:model.live.debounce.300ms="filtroUtente"
            class="form-control form-control-sm" placeholder="Utente…">
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroEntita" class="form-control form-control-sm">
            <option value="">Tutte le entità</option>
            <option value="Commessa">Commessa</option>
            <option value="Documento">Documento</option>
            <option value="MovimentoMagazzino">Movimento Magazzino</option>
            <option value="User">Utente</option>
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroAzione" class="form-control form-control-sm">
            <option value="">Tutte le azioni</option>
            <option value="created">Creazione</option>
            <option value="updated">Modifica</option>
            <option value="deleted">Eliminazione</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" wire:model.live="filtroDal"
            class="form-control form-control-sm" placeholder="Dal…">
        </div>
        <div class="col-md-2">
          <input type="date" wire:model.live="filtroAl"
            class="form-control form-control-sm" placeholder="Al…">
        </div>
        <div class="col-md-1">
          <button wire:click="$set('filtroUtente',''); $set('filtroEntita',''); $set('filtroAzione',''); $set('filtroDal',''); $set('filtroAl','')"
            class="btn btn-sm btn-outline-secondary w-100" title="Azzera filtri">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th style="width:140px">Data/Ora</th>
            <th>Utente</th>
            <th>Azione</th>
            <th>Entità</th>
            <th>Modifiche</th>
          </tr>
        </thead>
        <tbody>
          @forelse($attivita as $a)
          <tr>
            <td class="text-nowrap text-muted small">
              {{ $a->created_at->format('d/m/Y H:i') }}
            </td>
            <td>{{ $a->causer?->name ?? '—' }}</td>
            <td>
              @php
                $badges = [
                  'created' => 'success',
                  'updated' => 'warning',
                  'deleted' => 'danger',
                ];
                $evt = $a->event ?? $a->description;
                $color = $badges[$evt] ?? 'secondary';
              @endphp
              <span class="badge badge-{{ $color }}">{{ $evt }}</span>
            </td>
            <td>
              <span class="text-muted">{{ class_basename($a->subject_type ?? '') }}</span>
              @if($a->subject_id)
                <small class="text-muted">#{{ $a->subject_id }}</small>
              @endif
            </td>
            <td>
              @if($a->properties->get('old') || $a->properties->get('attributes'))
                <div class="small">
                  @foreach($a->properties->get('attributes', []) as $campo => $valoreNuovo)
                    @php $vecchio = $a->properties->get('old', [])[$campo] ?? null; @endphp
                    <div>
                      <strong>{{ $campo }}</strong>:
                      @if($vecchio !== null)
                        <span class="text-danger">{{ is_array($vecchio) ? json_encode($vecchio) : $vecchio }}</span>
                        → <span class="text-success">{{ is_array($valoreNuovo) ? json_encode($valoreNuovo) : $valoreNuovo }}</span>
                      @else
                        <span class="text-success">{{ is_array($valoreNuovo) ? json_encode($valoreNuovo) : $valoreNuovo }}</span>
                      @endif
                    </div>
                  @endforeach
                </div>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-3">Nessun evento trovato.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($attivita->hasPages())
    <div class="card-footer">
      {{ $attivita->links() }}
    </div>
    @endif
  </div>
</div>
