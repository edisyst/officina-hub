<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <input wire:model.live.debounce.300ms="cerca" type="text"
        class="form-control form-control-sm"
        placeholder="Cerca per ragione sociale o P.IVA..."
        style="width:280px">
    </div>
    <button wire:click="apriModal()" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> Nuova casa madre
    </button>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Ragione Sociale</th>
            <th>P.IVA</th>
            <th>Codice SDI</th>
            <th>Email</th>
            <th>Cod. Convenzionamento</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($caseMadri as $cm)
          <tr>
            <td><strong>{{ $cm->ragione_sociale }}</strong></td>
            <td>{{ $cm->partita_iva ?? '—' }}</td>
            <td>{{ $cm->codice_destinatario_sdi ?? '—' }}</td>
            <td>{{ $cm->email ?? '—' }}</td>
            <td>{{ $cm->codice_convenzionamento ?? '—' }}</td>
            <td class="text-right">
              <button wire:click="apriModal({{ $cm->id }})" class="btn btn-xs btn-info">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="elimina({{ $cm->id }})"
                wire:confirm="Eliminare {{ $cm->ragione_sociale }}?"
                class="btn btn-xs btn-danger ml-1">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted">Nessuna casa madre registrata.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Casa Madre' : 'Nuova Casa Madre' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Ragione Sociale *</label>
                <input wire:model="ragione_sociale" type="text"
                  class="form-control @error('ragione_sociale') is-invalid @enderror">
                @error('ragione_sociale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Partita IVA</label>
                <input wire:model="partita_iva" type="text" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Codice SDI (7 car.)</label>
                <input wire:model="codice_destinatario_sdi" type="text" maxlength="7" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>PEC</label>
                <input wire:model="pec" type="email" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Email</label>
                <input wire:model="email" type="email" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Telefono</label>
                <input wire:model="telefono" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Codice Convenzionamento</label>
                <input wire:model="codice_convenzionamento" type="text" class="form-control"
                  placeholder="Codice officina nella rete">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
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
