<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Azioni --}}
    <div class="mb-3">
        <button wire:click="apriModal" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Nuova campagna
        </button>
    </div>

    {{-- Lista campagne --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Campagne email</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Segmento</th>
                        <th>Stato</th>
                        <th>Pianificata</th>
                        <th class="text-right">Dest.</th>
                        <th class="text-right">Inviati</th>
                        <th class="text-right">Errori</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campagne as $c)
                    <tr>
                        <td>{{ $c->nome }}</td>
                        <td><span class="badge badge-secondary">{{ $segmenti[$c->segmento_target] ?? $c->segmento_target }}</span></td>
                        <td><span class="badge {{ $c->stato->badgeClass() }}">{{ $c->stato->label() }}</span></td>
                        <td>{{ $c->pianificata_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-right">{{ $c->totale_destinatari ?? '—' }}</td>
                        <td class="text-right">{{ $c->totale_inviati }}</td>
                        <td class="text-right">
                            @if ($c->totale_errori > 0)
                                <span class="text-danger">{{ $c->totale_errori }}</span>
                            @else
                                {{ $c->totale_errori }}
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            @if ($c->stato === \App\Enums\StatoCampagna::Completata)
                                <button wire:click="apriDettaglio({{ $c->id }})" class="btn btn-xs btn-outline-info">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            @endif
                            @if (in_array($c->stato->value, ['bozza', 'pianificata']))
                                <button wire:click="annulla({{ $c->id }})"
                                        wire:confirm="Annullare questa campagna?"
                                        class="btn btn-xs btn-outline-danger">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">Nessuna campagna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campagne->hasPages())
        <div class="card-footer">{{ $campagne->links() }}</div>
        @endif
    </div>

    {{-- Modal nuova campagna --}}
    @if ($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuova campagna email</h5>
                    <button type="button" class="close" wire:click="chiudiModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nome campagna *</label>
                                <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror">
                                @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label>Oggetto email *</label>
                                <input wire:model="oggetto" type="text" class="form-control @error('oggetto') is-invalid @enderror">
                                @error('oggetto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label>Corpo email *</label>
                                <textarea wire:model="corpo" class="form-control @error('corpo') is-invalid @enderror" rows="8"></textarea>
                                @error('corpo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Segmento target *</label>
                                <select wire:model.live="segmento_target" class="form-control">
                                    @foreach ($segmenti as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($conteggioDestinatari !== null)
                            <div class="alert alert-info py-2">
                                <i class="fas fa-users mr-1"></i>
                                <strong>{{ $conteggioDestinatari }}</strong> destinatari con consenso
                            </div>
                            @endif
                            <div class="form-group">
                                <label>Data invio pianificato</label>
                                <input wire:model="pianificata_at" type="datetime-local"
                                       class="form-control @error('pianificata_at') is-invalid @enderror">
                                @error('pianificata_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Lascia vuoto per bozza</small>
                            </div>
                            <div class="card card-outline card-secondary">
                                <div class="card-header p-2"><small class="font-weight-bold">Variabili disponibili</small></div>
                                <div class="card-body p-2">
                                    @foreach ($variabiliDisponibili as $v)
                                        <code class="d-block text-xs">@{{ {{ $v }} }}</code>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click="chiudiModal" class="btn btn-secondary btn-sm">Annulla</button>
                    <button type="button" wire:click="salvaBozza" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-save mr-1"></i> Salva bozza
                    </button>
                    <button type="button" wire:click="inviaSubito" class="btn btn-warning btn-sm">
                        <i class="fas fa-paper-plane mr-1"></i> Invia subito
                    </button>
                    @if ($pianificata_at)
                    <button type="button" wire:click="pianifica" class="btn btn-primary btn-sm">
                        <i class="fas fa-clock mr-1"></i> Pianifica
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Dettaglio campagna completata --}}
    @if ($showDettaglio && $campagnaDettaglio)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Statistiche: {{ $campagnaDettaglio->nome }}</h5>
                    <button type="button" class="close" wire:click="chiudiDettaglio">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Destinatari</span>
                                    <span class="info-box-number">{{ $campagnaDettaglio->totale_destinatari ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Inviati</span>
                                    <span class="info-box-number">{{ $campagnaDettaglio->totale_inviati }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Errori</span>
                                    <span class="info-box-number">{{ $campagnaDettaglio->totale_errori }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tasso consegna</span>
                                    <span class="info-box-number">
                                        @php
                                            $tot = $campagnaDettaglio->totale_destinatari ?: 1;
                                            echo round(($campagnaDettaglio->totale_inviati / $tot) * 100, 1) . '%';
                                        @endphp
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr><th>Cliente</th><th>Stato</th><th>Inviata</th><th>Errore</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($campagnaDettaglio->invii as $inv)
                            <tr>
                                <td>{{ $inv->cliente->nome_completo }}</td>
                                <td>
                                    <span class="badge {{ $inv->stato === 'inviata' ? 'badge-success' : ($inv->stato === 'fallita' ? 'badge-danger' : 'badge-secondary') }}">
                                        {{ $inv->stato }}
                                    </span>
                                </td>
                                <td>{{ $inv->inviata_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td><small class="text-danger">{{ $inv->errore }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button wire:click="chiudiDettaglio" class="btn btn-secondary btn-sm">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
