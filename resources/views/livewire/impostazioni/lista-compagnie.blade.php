<div>
  @if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-building mr-2"></i>Compagnie Assicurative</h3>
      <div class="card-tools">
        @can('create', \App\Models\CompagniaAssicurativa::class)
        <button wire:click="apriCrea" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i> Nuova Compagnia
        </button>
        @endcan
      </div>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4">
          <input type="text" wire:model.live.debounce.300ms="search"
            class="form-control form-control-sm" placeholder="Cerca per nome o codice ABI...">
        </div>
      </div>

      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Codice ABI</th>
            <th>Email / PEC</th>
            <th>Telefono</th>
            <th>Referente</th>
            <th width="100"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($compagnie as $compagnia)
          <tr>
            <td><strong>{{ $compagnia->nome }}</strong></td>
            <td><code>{{ $compagnia->codice_abi ?? '—' }}</code></td>
            <td>
              @if($compagnia->email) {{ $compagnia->email }}<br> @endif
              @if($compagnia->pec) <small class="text-muted">PEC: {{ $compagnia->pec }}</small> @endif
              @if(!$compagnia->email && !$compagnia->pec) — @endif
            </td>
            <td>{{ $compagnia->telefono ?? '—' }}</td>
            <td>{{ $compagnia->referente ?? '—' }}</td>
            <td class="text-right">
              @can('update', $compagnia)
              <button wire:click="apriModifica({{ $compagnia->id }})" class="btn btn-xs btn-outline-primary">
                <i class="fas fa-edit"></i>
              </button>
              @endcan
              @can('delete', $compagnia)
              <button wire:click="elimina({{ $compagnia->id }})"
                wire:confirm="Eliminare questa compagnia?"
                class="btn btn-xs btn-outline-danger">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-3">Nessuna compagnia trovata.</td></tr>
          @endforelse
        </tbody>
      </table>

      {{ $compagnie->links() }}
    </div>
  </div>

  {{-- Modal Crea/Modifica --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Compagnia' : 'Nuova Compagnia' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Nome <span class="text-danger">*</span></label>
                <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ragione sociale">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Codice ABI</label>
                <input type="text" wire:model="codice_abi" class="form-control @error('codice_abi') is-invalid @enderror" placeholder="es. 01030">
                @error('codice_abi')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>PEC</label>
                <input type="email" wire:model="pec" class="form-control @error('pec') is-invalid @enderror">
                @error('pec')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Telefono</label>
                <input type="text" wire:model="telefono" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Referente (liquidatore)</label>
                <input type="text" wire:model="referente" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Indirizzo</label>
                <input type="text" wire:model="indirizzo" class="form-control">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Note</label>
                <textarea wire:model="note" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
