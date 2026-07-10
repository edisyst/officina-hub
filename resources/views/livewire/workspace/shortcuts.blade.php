<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-star mr-2 text-warning"></i>Le mie scorciatoie</h3>
      <div class="card-tools">
        <small class="text-muted">Trascina per riordinare</small>
      </div>
    </div>
    <div class="card-body p-0">
      @if(empty($shortcuts))
        <p class="p-3 text-muted mb-0">Nessuna scorciatoia salvata. Usa la <i class="far fa-star text-warning"></i> in navbar per aggiungerne.</p>
      @else
        <ul
          class="list-group list-group-flush"
          id="shortcuts-sortable"
          x-data="{
            init() {
              if (typeof Sortable !== 'undefined') {
                new Sortable(this.$el, {
                  animation: 150,
                  handle: '.drag-handle',
                  onEnd: (evt) => {
                    const ids = Array.from(this.$el.querySelectorAll('[data-id]'))
                      .map(el => el.dataset.id);
                    $wire.reorder(ids);
                  }
                });
              }
            }
          }"
          x-init="init()"
        >
          @foreach($shortcuts as $sc)
          <li class="list-group-item d-flex align-items-center" data-id="{{ $sc['id'] }}">
            <span class="drag-handle mr-3 text-muted" style="cursor:grab">
              <i class="fas fa-grip-vertical"></i>
            </span>

            <i class="{{ $sc['icon'] ?? 'fas fa-star' }} mr-2 text-warning"></i>

            @if($editingId === $sc['id'])
              <input
                type="text"
                class="form-control form-control-sm mr-2"
                wire:model="editLabel"
                wire:keydown.enter="saveEdit"
                wire:keydown.escape="cancelEdit"
                style="max-width:300px"
                autofocus
              >
              <button class="btn btn-sm btn-primary mr-1" wire:click="saveEdit">
                <i class="fas fa-check"></i>
              </button>
              <button class="btn btn-sm btn-secondary" wire:click="cancelEdit">
                <i class="fas fa-times"></i>
              </button>
            @else
              <a href="{{ $sc['url'] }}" class="flex-grow-1">{{ $sc['label'] }}</a>
              <div class="ml-auto d-flex" style="gap:.25rem">
                <button class="btn btn-xs btn-outline-secondary" wire:click="moveUp({{ $sc['id'] }})" title="Su">
                  <i class="fas fa-arrow-up"></i>
                </button>
                <button class="btn btn-xs btn-outline-secondary" wire:click="moveDown({{ $sc['id'] }})" title="Giù">
                  <i class="fas fa-arrow-down"></i>
                </button>
                <button class="btn btn-xs btn-outline-primary" wire:click="startEdit({{ $sc['id'] }})" title="Rinomina">
                  <i class="fas fa-pencil-alt"></i>
                </button>
                <button
                  class="btn btn-xs btn-outline-danger"
                  wire:click="delete({{ $sc['id'] }})"
                  wire:confirm="Rimuovere la scorciatoia '{{ $sc['label'] }}'?"
                  title="Elimina"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            @endif
          </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</div>
