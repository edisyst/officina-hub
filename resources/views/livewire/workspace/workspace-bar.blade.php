<div class="d-inline-block" x-data>
  {{-- Star button --}}
  <button
    wire:click="toggle"
    class="nav-link btn btn-link"
    title="{{ $isStarred ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti' }}"
    style="cursor:pointer;border:none;background:none"
  >
    <i class="{{ $isStarred ? 'fas' : 'far' }} fa-star {{ $isStarred ? 'text-warning' : '' }}"></i>
  </button>

  {{-- Label popover --}}
  @if($showLabelPopover)
  <div class="card card-sm shadow-sm position-absolute" style="right:0;top:40px;z-index:9999;min-width:280px">
    <div class="card-body p-2">
      <p class="mb-1 small text-muted">Nome scorciatoia</p>
      <input
        type="text"
        class="form-control form-control-sm mb-2"
        wire:model="editLabel"
        wire:keydown.enter="saveShortcut"
        wire:keydown.escape="cancelPopover"
        placeholder="Es. Commesse aperte"
        autofocus
      >
      <div class="d-flex" style="gap:.25rem">
        <button class="btn btn-sm btn-primary flex-grow-1" wire:click="saveShortcut">
          <i class="fas fa-check mr-1"></i>Salva
        </button>
        <button class="btn btn-sm btn-secondary" wire:click="cancelPopover">Annulla</button>
      </div>
    </div>
  </div>
  @endif
</div>
