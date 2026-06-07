<div>
  @if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-car-side mr-2"></i>Ponti e Postazioni</h3>
      <div class="card-tools">
        <button wire:click="apriNuovo" class="btn btn-sm btn-primary">
          <i class="fas fa-plus"></i> Nuovo Ponte
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div wire:ignore
        x-data="{
          initSort() {
            const el = document.getElementById('ponti-sortable');
            if (!el) return;
            new Sortable(el, {
              handle: '.drag-handle',
              animation: 150,
              onEnd(evt) {
                const ids = [...el.querySelectorAll('[data-id]')].map(e => parseInt(e.dataset.id));
                $wire.aggiornaSortable(ids);
              }
            });
          }
        }"
        x-init="initSort()"
      >
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:30px"></th>
              <th>Nome</th>
              <th>Tipo</th>
              <th>Descrizione</th>
              <th>Stato</th>
              <th>Azioni</th>
            </tr>
          </thead>
          <tbody id="ponti-sortable">
            @foreach($ponti as $ponte)
            <tr data-id="{{ $ponte->id }}">
              <td class="text-center drag-handle" style="cursor:grab"><i class="fas fa-grip-vertical text-muted"></i></td>
              <td>{{ $ponte->nome }}</td>
              <td><span class="badge badge-secondary">{{ $ponte->tipo->label() }}</span></td>
              <td>{{ $ponte->descrizione ?? '—' }}</td>
              <td>
                <span
                  x-data="{ attivo: {{ $ponte->attivo ? 'true' : 'false' }} }"
                  x-on:click.prevent="
                    if (confirm('Cambiare lo stato di questo ponte?')) {
                      $wire.toggleAttivo({{ $ponte->id }});
                    }
                  "
                  style="cursor:pointer"
                  class="badge {{ $ponte->attivo ? 'badge-success' : 'badge-secondary' }}">
                  {{ $ponte->attivo ? 'Attivo' : 'Inattivo' }}
                </span>
              </td>
              <td>
                <button wire:click="apriModifica({{ $ponte->id }})" class="btn btn-xs btn-outline-primary">
                  <i class="fas fa-edit"></i> Modifica
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $ponteId ? 'Modifica Ponte' : 'Nuovo Ponte' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nome <span class="text-danger">*</span></label>
            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="es. Ponte 1">
            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Tipo <span class="text-danger">*</span></label>
            <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
              @foreach($tipiPonte as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
              @endforeach
            </select>
            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Descrizione</label>
            <input type="text" wire:model="descrizione" class="form-control" placeholder="Descrizione opzionale">
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
