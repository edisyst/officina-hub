<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Clienti</h3>
      <div class="card-tools d-flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text"
          class="form-control form-control-sm mr-2" placeholder="Cerca per nome, CF, P.IVA..." style="width:200px">
        <select wire:model.live="filtroSegmento" class="form-control form-control-sm" style="width:130px">
          <option value="">Tutti segmenti</option>
          @foreach ($segmentiCrm as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
          @endforeach
        </select>
        <select wire:model.live="filtroConsenso" class="form-control form-control-sm" style="width:140px">
          <option value="">Tutti (consenso)</option>
          <option value="1">Con consenso</option>
          <option value="0">Senza consenso</option>
        </select>
        @can('create', \App\Models\Cliente::class)
        <button wire:click="apriModal()" class="btn btn-sm btn-primary">
          <i class="fas fa-plus"></i> Nuovo
        </button>
        @endcan
        @role('admin')
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-success">
          <i class="fas fa-file-csv"></i> CSV
        </button>
        <button wire:click="$toggle('showTrashedModal')" class="btn btn-sm btn-secondary">
          <i class="fas fa-trash-restore"></i>
        </button>
        @endrole
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Nome / Ragione Sociale</th>
            <th>Tipo</th>
            <th>Telefono</th>
            <th>Email</th>
            <th>Città</th>
            <th>Segmento</th>
            <th width="120">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($clienti as $cliente)
          <tr>
            <td>
              <a href="{{ route('clienti.show', $cliente->id) }}" class="font-weight-bold">
                {{ $cliente->nome_completo }}
              </a>
              @if($cliente->codice_fiscale)
              <br><small class="text-muted">CF: {{ $cliente->codice_fiscale }}</small>
              @endif
            </td>
            <td>{{ $cliente->tipo->label() }}</td>
            <td>{{ $cliente->telefono ?? '-' }}</td>
            <td>{{ $cliente->email ?? '-' }}</td>
            <td>{{ $cliente->citta ?? '-' }}</td>
            <td>
              @if ($cliente->segmento_crm)
                <span class="badge {{ $cliente->segmento_crm->badgeClass() }}">{{ $cliente->segmento_crm->label() }}</span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              @can('update', $cliente)
              <button wire:click="apriModal({{ $cliente->id }})" class="btn btn-xs btn-info" title="Modifica">
                <i class="fas fa-edit"></i>
              </button>
              @endcan
              @can('delete', $cliente)
              <button wire:click="elimina({{ $cliente->id }})"
                wire:confirm="Eliminare il cliente '{{ $cliente->nome_completo }}'?"
                class="btn btn-xs btn-danger" title="Elimina">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-3">Nessun cliente trovato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      {{ $clienti->links() }}
    </div>
  </div>

  <!-- Modal Crea/Modifica Cliente -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Cliente' : 'Nuovo Cliente' }}</h5>
          <button type="button" class="close" wire:click="chiudiModal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo *</label>
                <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                  @foreach($tipiCliente as $t)
                  <option value="{{ $t->value }}">{{ $t->label() }}</option>
                  @endforeach
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          @if($tipo === 'fisica')
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nome</label>
                <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Cognome</label>
                <input wire:model="cognome" type="text" class="form-control @error('cognome') is-invalid @enderror">
                @error('cognome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          @else
          <div class="form-group">
            <label>Ragione Sociale</label>
            <input wire:model="ragione_sociale" type="text" class="form-control @error('ragione_sociale') is-invalid @enderror">
            @error('ragione_sociale')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          @endif

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Codice Fiscale</label>
                <input wire:model="codice_fiscale" type="text" class="form-control @error('codice_fiscale') is-invalid @enderror">
                @error('codice_fiscale')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Partita IVA</label>
                <input wire:model="partita_iva" type="text" class="form-control @error('partita_iva') is-invalid @enderror">
                @error('partita_iva')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Telefono</label>
                <input wire:model="telefono" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Indirizzo</label>
            <input wire:model="indirizzo" type="text" class="form-control">
          </div>
          <div class="row">
            <div class="col-md-5">
              <div class="form-group">
                <label>Città</label>
                <input wire:model="citta" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>CAP</label>
                <input wire:model="cap" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Provincia</label>
                <input wire:model="provincia" type="text" class="form-control" maxlength="2" placeholder="RM">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="chiudiModal">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Modal Clienti Eliminati (solo admin) -->
  @if($showTrashedModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Clienti Eliminati</h5>
          <button type="button" class="close" wire:click="$toggle('showTrashedModal')"><span>&times;</span></button>
        </div>
        <div class="modal-body p-0">
          <table class="table table-sm mb-0">
            <thead>
              <tr><th>Nome</th><th>Eliminato il</th><th></th></tr>
            </thead>
            <tbody>
              @forelse($eliminati as $cliente)
              <tr>
                <td>{{ $cliente->nome_completo }}</td>
                <td>{{ $cliente->deleted_at->format('d/m/Y H:i') }}</td>
                <td>
                  <button wire:click="ripristina({{ $cliente->id }})" class="btn btn-xs btn-success">
                    <i class="fas fa-undo"></i> Ripristina
                  </button>
                </td>
              </tr>
              @empty
              <tr><td colspan="3" class="text-center text-muted">Nessun cliente eliminato.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
