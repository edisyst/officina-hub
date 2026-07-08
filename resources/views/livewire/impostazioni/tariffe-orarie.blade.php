<div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Tariffe orarie manodopera</h6>
    <button wire:click="apriNuovo" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> Nuova tariffa
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif

  <table class="table table-sm table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th class="text-right">€/ora</th>
        <th class="text-center">Stato</th>
        <th class="text-center">Default</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($tariffe as $t)
      <tr>
        <td>
          {{ $t->nome }}
          @if($t->is_default)
            <span class="badge badge-success ml-1">Default</span>
          @endif
        </td>
        <td class="text-right font-weight-bold">€ {{ number_format($t->tariffa_oraria, 2, ',', '.') }}</td>
        <td class="text-center">
          <button wire:click="toggleAttiva({{ $t->id }})"
            class="btn btn-xs {{ $t->is_attiva ? 'btn-success' : 'btn-secondary' }}">
            {{ $t->is_attiva ? 'Attiva' : 'Inattiva' }}
          </button>
        </td>
        <td class="text-center">
          @if(! $t->is_default)
          <button wire:click="impostaDefault({{ $t->id }})" class="btn btn-xs btn-outline-primary">
            Imposta default
          </button>
          @else
          <span class="text-success"><i class="fas fa-check-circle"></i></span>
          @endif
        </td>
        <td class="text-right">
          <button wire:click="apriModifica({{ $t->id }})" class="btn btn-xs btn-info">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="text-center text-muted">Nessuna tariffa oraria configurata.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $tariffaId ? 'Modifica tariffa' : 'Nuova tariffa oraria' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nome *</label>
            <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror"
              placeholder="es. Meccanica, Elettrauto, Carrozzeria">
            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Tariffa oraria (€/h) *</label>
            <input wire:model="tariffaOraria" type="number" step="0.01" min="0"
              class="form-control @error('tariffaOraria') is-invalid @enderror">
            @error('tariffaOraria')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="to_attiva" wire:model="is_attiva">
            <label class="custom-control-label" for="to_attiva">Tariffa attiva</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" wire:click="$set('showModal',false)" class="btn btn-secondary">Annulla</button>
          <button type="button" wire:click="salva" class="btn btn-primary">Salva</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
