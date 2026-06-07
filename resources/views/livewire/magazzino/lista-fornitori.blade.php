<div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="input-group" style="max-width:350px">
      <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
        placeholder="Cerca ragione sociale, P.IVA…">
      <div class="input-group-append">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
      </div>
    </div>
    @can('create', \App\Models\Fornitore::class)
    <button wire:click="apriModal()" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Nuovo fornitore
    </button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Ragione sociale</th>
            <th>P.IVA</th>
            <th>Telefono</th>
            <th>Email</th>
            <th>Città</th>
            <th>Articoli</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($fornitori as $f)
          <tr>
            <td class="font-weight-bold">{{ $f->ragione_sociale }}</td>
            <td>{{ $f->partita_iva ?? '—' }}</td>
            <td>{{ $f->telefono ?? '—' }}</td>
            <td>{{ $f->email ?? '—' }}</td>
            <td>{{ $f->citta ?? '—' }}</td>
            <td>
              <span class="badge badge-secondary">{{ $f->articoli()->count() }}</span>
            </td>
            <td class="text-right">
              @can('update', $f)
              <button wire:click="apriModal({{ $f->id }})" class="btn btn-xs btn-info">
                <i class="fas fa-edit"></i>
              </button>
              @endcan
              @can('delete', $f)
              <button wire:click="elimina({{ $f->id }})"
                wire:confirm="Eliminare il fornitore «{{ $f->ragione_sociale }}»?"
                class="btn btn-xs btn-danger">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-4">Nessun fornitore trovato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($fornitori->hasPages())
    <div class="card-footer">{{ $fornitori->links() }}</div>
    @endif
  </div>

  <!-- Modal Fornitore -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica fornitore' : 'Nuovo fornitore' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Ragione sociale *</label>
                <input wire:model="ragione_sociale" type="text" class="form-control @error('ragione_sociale') is-invalid @enderror">
                @error('ragione_sociale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>P.IVA</label>
                <input wire:model="partita_iva" type="text" class="form-control @error('partita_iva') is-invalid @enderror">
                @error('partita_iva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Codice fiscale</label>
                <input wire:model="codice_fiscale" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Telefono</label>
                <input wire:model="telefono" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Email</label>
                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Indirizzo</label>
                <input wire:model="indirizzo" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Città</label>
                <input wire:model="citta" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>CAP</label>
                <input wire:model="cap" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-1">
              <div class="form-group">
                <label>Prov.</label>
                <input wire:model="provincia" type="text" class="form-control" maxlength="2">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal',false)">Annulla</button>
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
