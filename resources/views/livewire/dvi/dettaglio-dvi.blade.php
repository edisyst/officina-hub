<div>
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

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><i class="fas fa-camera-retro mr-2"></i>Digital Vehicle Inspection</h6>
    @if(!$haInviata)
    <a href="{{ route('dvi.nuova', $commessaId) }}" class="btn btn-sm btn-primary">
      <i class="fas fa-plus mr-1"></i>Nuova DVI
    </a>
    @endif
  </div>

  @forelse($ispezioni as $ispezione)
  <div class="card card-outline card-{{ $ispezione->stato->badgeClass() === 'badge-success' ? 'success' : ($ispezione->stato->badgeClass() === 'badge-warning' ? 'warning' : 'secondary') }} mb-3">
    <div class="card-header">
      <h3 class="card-title">
        <span class="badge {{ $ispezione->stato->badgeClass() }}">{{ $ispezione->stato->label() }}</span>
        DVI #{{ $ispezione->id }}
        <small class="text-muted ml-2">— {{ $ispezione->user->name ?? '—' }}</small>
      </h3>
      <div class="card-tools d-flex" style="gap:.25rem">
        @if($ispezione->stato->value === 'bozza')
        <a href="{{ route('dvi.nuova', $ispezione->commessa_id) }}" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-edit mr-1"></i>Modifica
        </a>
        @endif
        <a href="{{ route('dvi.anteprima', $ispezione->id) }}" class="btn btn-sm btn-outline-info" target="_blank">
          <i class="fas fa-eye mr-1"></i>Anteprima
        </a>
        @if(in_array($ispezione->stato->value, ['approvata','parzialmente_approvata','rifiutata']))
        <button wire:click="vediRisposta({{ $ispezione->id }})" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-comment mr-1"></i>Risposta
        </button>
        @hasanyrole('admin|accettatore|cassa')
        <button wire:click="convertiInPreventivo({{ $ispezione->id }})"
          wire:confirm="Aggiungere le voci approvate al preventivo della commessa?"
          class="btn btn-sm btn-warning">
          <span wire:loading wire:target="convertiInPreventivo({{ $ispezione->id }})" class="spinner-border spinner-border-sm mr-1"></span>
          <i class="fas fa-file-invoice mr-1"></i>→ Preventivo
        </button>
        @endhasanyrole
        @endif
      </div>
    </div>
    <div class="card-body py-2">
      <div class="row text-sm">
        <div class="col-6 col-md-3">
          <small class="text-muted d-block">Creata</small>
          {{ $ispezione->created_at->format('d/m/Y H:i') }}
        </div>
        @if($ispezione->inviata_at)
        <div class="col-6 col-md-3">
          <small class="text-muted d-block">Inviata</small>
          {{ $ispezione->inviata_at->format('d/m/Y H:i') }}
        </div>
        @endif
        @if($ispezione->approvata_at)
        <div class="col-6 col-md-3">
          <small class="text-muted d-block">Risposta</small>
          {{ $ispezione->approvata_at->format('d/m/Y H:i') }}
        </div>
        @endif
        @php $importo = $ispezione->voci->where('stato_approvazione', \App\Enums\StatoApprovazioneDvi::Approvato)->sum('prezzo_stimato'); @endphp
        @if($importo > 0)
        <div class="col-6 col-md-3">
          <small class="text-muted d-block">Importo approvato</small>
          <strong class="text-success">€ {{ number_format($importo, 2, ',', '.') }}</strong>
        </div>
        @endif
      </div>
      <div class="mt-2">
        @foreach($ispezione->voci as $voce)
        <span class="badge {{ $voce->urgenza->badgeClass() }} mr-1 mb-1">
          {{ $voce->descrizione }}
          @if($voce->stato_approvazione)
          <i class="{{ $voce->stato_approvazione === \App\Enums\StatoApprovazioneDvi::Approvato ? 'fas fa-check ml-1' : 'fas fa-clock ml-1' }}"></i>
          @endif
        </span>
        @endforeach
      </div>
    </div>
  </div>
  @empty
  <div class="text-center text-muted py-4">
    <i class="fas fa-camera-retro fa-3x mb-3 d-block opacity-50"></i>
    Nessuna DVI per questa commessa.
    @if(!$haInviata)
    <br><a href="{{ route('dvi.nuova', $commessaId) }}" class="btn btn-sm btn-primary mt-2">
      <i class="fas fa-plus mr-1"></i>Crea la prima DVI
    </a>
    @endif
  </div>
  @endforelse

  <!-- Modal dettaglio risposta -->
  @if($showModalRisposta && $ispezioneSelezionata)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-comment-alt mr-2"></i>Risposta cliente — DVI #{{ $ispezioneSelezionata->id }}
          </h5>
          <button type="button" class="close" wire:click="$set('showModalRisposta',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          @if($ispezioneSelezionata->note_cliente)
          <div class="alert alert-info">
            <strong>Note del cliente:</strong><br>
            {{ $ispezioneSelezionata->note_cliente }}
          </div>
          @endif
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Voce</th>
                <th>Urgenza</th>
                <th>Prezzo</th>
                <th>Risposta</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ispezioneSelezionata->voci as $voce)
              <tr>
                <td>
                  <strong>{{ $voce->categoria }}</strong><br>
                  <small>{{ $voce->descrizione }}</small>
                </td>
                <td><span class="badge {{ $voce->urgenza->badgeClass() }}">{{ $voce->urgenza->label() }}</span></td>
                <td>{{ $voce->prezzo_stimato ? '€ '.number_format($voce->prezzo_stimato,2,',','.') : '—' }}</td>
                <td>
                  @if($voce->stato_approvazione)
                  <span class="badge {{ $voce->stato_approvazione->badgeClass() }}">{{ $voce->stato_approvazione->label() }}</span>
                  @else
                  <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModalRisposta',false)">Chiudi</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
