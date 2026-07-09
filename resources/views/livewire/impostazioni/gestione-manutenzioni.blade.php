<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-history mr-2 text-primary"></i>Regole manutenzione</h5>
    <button class="btn btn-primary btn-sm" wire:click="apriNuovo">
      <i class="fas fa-plus mr-1"></i>Nuova regola
    </button>
  </div>

  <div class="card card-outline card-primary">
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Nome</th>
            <th>Ogni (km)</th>
            <th>Ogni (mesi)</th>
            <th style="width:80px">Attiva</th>
            <th style="width:120px"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($rules as $rule)
          <tr class="{{ $rule->is_active ? '' : 'text-muted' }}">
            <td>{{ $rule->name }}</td>
            <td>{{ $rule->every_km ? number_format($rule->every_km, 0, ',', '.') : '—' }}</td>
            <td>{{ $rule->every_months ?? '—' }}</td>
            <td>
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="active-{{ $rule->id }}"
                  {{ $rule->is_active ? 'checked' : '' }}
                  wire:click="toggleAttivo({{ $rule->id }})">
                <label class="custom-control-label" for="active-{{ $rule->id }}"></label>
              </div>
            </td>
            <td class="text-right">
              <button class="btn btn-xs btn-info mr-1" wire:click="apriModifica({{ $rule->id }})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-xs btn-danger"
                wire:click="elimina({{ $rule->id }})"
                wire:confirm="Eliminare la regola '{{ addslashes($rule->name) }}'?">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-3">Nessuna regola definita</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $ruleId ? 'Modifica regola' : 'Nuova regola' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
              wire:model="name" placeholder="Es. Tagliando olio" maxlength="200">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Ogni km</label>
                <input type="number" class="form-control @error('every_km') is-invalid @enderror"
                  wire:model="every_km" placeholder="Es. 15000" min="1">
                @error('every_km')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Ogni mesi</label>
                <input type="number" class="form-control @error('every_months') is-invalid @enderror"
                  wire:model="every_months" placeholder="Es. 12" min="1">
                @error('every_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <small class="text-muted">Almeno un intervallo (km o mesi) obbligatorio.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button class="btn btn-primary" wire:click="salva">Salva</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
