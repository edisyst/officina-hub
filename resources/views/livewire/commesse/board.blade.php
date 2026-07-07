<div x-data="boardComponent()" wire:poll.10s>

  {{-- Toolbar --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row align-items-center">
        <div class="col-md-4">
          <input wire:model.live.debounce.300ms="search" type="text"
            class="form-control form-control-sm" placeholder="Cerca numero, cliente, targa...">
        </div>
        <div class="col-md-3">
          <select wire:model.live="filtroMeccanico" class="form-control form-control-sm">
            <option value="">Tutti i responsabili</option>
            @foreach($meccanici as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 text-right">
          <a href="{{ route('commesse.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-list"></i> Vista tabella
          </a>
          @can('create', \App\Models\Commessa::class)
          <a href="{{ route('commesse.create') }}" class="btn btn-sm btn-success ml-1">
            <i class="fas fa-plus"></i> Nuova
          </a>
          @endcan
        </div>
      </div>
    </div>
  </div>

  {{-- Toast errore transizione --}}
  @if($errorMessage)
  <div class="alert alert-danger alert-dismissible fade show mb-3">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{ $errorMessage }}
  </div>
  @endif

  {{-- Kanban board --}}
  <div class="d-flex overflow-auto pb-3" style="gap: 12px; align-items: flex-start;">
    @foreach($colonne as $stateValue => $col)
    @php $stato = $col['stato']; @endphp
    <div class="flex-shrink-0" style="width: 260px;">
      <div class="card mb-0 h-100">

        {{-- Column header --}}
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between"
             style="background: #f4f6f9;">
          <div>
            <span class="badge {{ $stato->badgeClass() }} mr-1">{{ $col['count'] }}</span>
            <strong class="text-sm">{{ $stato->label() }}</strong>
          </div>
          @if($col['ore_stimate'] > 0)
          <small class="text-muted" title="Ore manodopera stimate">
            <i class="fas fa-clock"></i> {{ $col['ore_stimate'] }}h
          </small>
          @endif
        </div>

        {{-- Column body (Sortable target) --}}
        <div class="card-body p-2 board-column-body"
             data-board-column="{{ $stateValue }}"
             style="min-height: 80px; max-height: 75vh; overflow-y: auto;">

          @forelse($col['commesse'] as $c)
          @php
            $giorni = $c->updated_at ? now()->diffInDays($c->updated_at) : 0;
            $isStale = $giorni >= $staleDays;
            $hasMancanza = $c->righe->contains(function ($r) {
              return $r->tipo === \App\Enums\TipoRiga::Articolo
                && $r->articolo
                && $r->articolo->giacenza_attuale < (float) $r->quantita;
            });
          @endphp
          <div class="card card-body py-2 px-3 mb-2 shadow-sm board-card"
               data-commessa-id="{{ $c->id }}"
               style="cursor: grab; border-left: 3px solid {{ $isStale ? '#dc3545' : '#dee2e6' }};">

            <div class="d-flex justify-content-between align-items-start mb-1">
              <a href="{{ route('commesse.show', $c->id) }}"
                 class="font-weight-bold text-sm"
                 style="font-size: 13px;"
                 onclick="event.stopPropagation()">
                {{ $c->numero }}
              </a>
              <div class="d-flex gap-1" style="gap: 4px;">
                @if($hasMancanza)
                <span class="badge badge-warning" title="Ricambi con giacenza insufficiente">
                  <i class="fas fa-exclamation-triangle"></i>
                </span>
                @endif
                @if($isStale)
                <span class="badge badge-danger" title="{{ $giorni }} giorni nello stato corrente">
                  {{ $giorni }}g
                </span>
                @endif
              </div>
            </div>

            <div style="font-size: 12px;">
              <strong>{{ $c->veicolo->targa ?? '-' }}</strong>
              <span class="text-muted">{{ $c->veicolo->marca ?? '' }} {{ $c->veicolo->modello ?? '' }}</span>
            </div>
            <div class="text-muted" style="font-size: 12px;">
              {{ $c->cliente->nome_completo ?? '-' }}
            </div>
            @if($c->user)
            <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
              <i class="fas fa-user" style="font-size: 10px;"></i> {{ $c->user->name }}
            </div>
            @endif

          </div>
          @empty
          <p class="text-muted text-center mt-3 mb-0" style="font-size: 12px;">Nessuna commessa</p>
          @endforelse
        </div>

      </div>
    </div>
    @endforeach
  </div>

</div>
