<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-stream mr-2"></i>Feed Attività</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" wire:model.live="filterUser">
                                <option value="">Tutti gli utenti</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" wire:model.live="filterType">
                                <option value="">Tutti i tipi</option>
                                @foreach($entityTypes as $class => $label)
                                <option value="{{ $class }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" wire:model.live="filterDateFrom" placeholder="Dal">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" wire:model.live="filterDateTo" placeholder="Al">
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('success') }}
                    </div>
                    @endif
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Attività</th>
                                    <th>Utente</th>
                                    <th>Quando</th>
                                    <th style="width:180px;">Stato / Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $feedService->humanize($activity) }}</td>
                                    <td>{{ $activity->causer?->name ?? 'Sistema' }}</td>
                                    <td>
                                        <span title="{{ $activity->created_at->format('d/m/Y H:i:s') }}">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($activity->properties['undone_at']))
                                            <span class="badge badge-secondary">Annullata</span>
                                        @elseif($undoService->canUndo($activity, $user))
                                            <button
                                                class="btn btn-xs btn-warning"
                                                wire:click="undo({{ $activity->id }})"
                                                wire:confirm="Annullare questa operazione?"
                                            >
                                                <i class="fas fa-undo"></i> Annulla
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Nessuna attività trovata.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $activities->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
