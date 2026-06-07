<div>

  {{-- Feedback --}}
  @if($esito)
  <div class="alert alert-{{ $esitoTipo }} alert-dismissible">
    <button type="button" class="close" wire:click="$set('esito','')"><span>&times;</span></button>
    {{ $esito }}
  </div>
  @endif

  {{-- Sezione SMTP --}}
  <div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Configurazione SMTP</h3></div>
    <div class="card-body">

      <div class="form-group">
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input" id="toggleEmail" wire:model="abilitato">
          <label class="custom-control-label" for="toggleEmail">Abilita notifiche email</label>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Host SMTP</label>
            <input type="text" class="form-control" wire:model="smtpHost" placeholder="smtp.gmail.com">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Porta</label>
            <input type="number" class="form-control" wire:model="smtpPort" placeholder="587">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Cifratura</label>
            <select class="form-control" wire:model="smtpEncryption">
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
              <option value="none">Nessuna</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" wire:model="smtpUsername" autocomplete="off">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Password <small class="text-muted">(lasciare vuoto per non modificare)</small></label>
            <input type="password" class="form-control" wire:model="smtpPassword" autocomplete="new-password">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Indirizzo mittente</label>
            <input type="email" class="form-control @error('fromAddress') is-invalid @enderror"
              wire:model="fromAddress">
            @error('fromAddress')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Nome mittente</label>
            <input type="text" class="form-control" wire:model="fromName">
          </div>
        </div>
      </div>

      <hr>
      <h5>Promemoria scadenze (giorni di anticipo)</h5>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Revisione</label>
            <div class="input-group">
              <input type="number" class="form-control" wire:model="promemoriaRevisione" min="1" max="365">
              <div class="input-group-append"><span class="input-group-text">gg</span></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Tagliando</label>
            <div class="input-group">
              <input type="number" class="form-control" wire:model="promemoriaTagliando" min="1" max="365">
              <div class="input-group-append"><span class="input-group-text">gg</span></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Assicurazione</label>
            <div class="input-group">
              <input type="number" class="form-control" wire:model="promemoriaAssicurazione" min="1" max="365">
              <div class="input-group-append"><span class="input-group-text">gg</span></div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <button class="btn btn-primary" wire:click="salvaSmtp" wire:loading.attr="disabled">
        <span wire:loading wire:target="salvaSmtp"><i class="fas fa-spinner fa-spin mr-1"></i></span>
        <i class="fas fa-save mr-1"></i> Salva SMTP
      </button>
      <div class="d-flex align-items-center">
        <input type="email" class="form-control form-control-sm mr-2" wire:model="emailTest"
          placeholder="email per test" style="width:220px">
        <button class="btn btn-outline-secondary btn-sm" wire:click="testConnessione"
          wire:loading.attr="disabled" wire:target="testConnessione">
          <span wire:loading wire:target="testConnessione"><i class="fas fa-spinner fa-spin mr-1"></i></span>
          <i class="fas fa-vial mr-1"></i> Test connessione
        </button>
      </div>
    </div>
  </div>

  {{-- Sezione Template --}}
  <div class="card">
    <div class="card-header"><h3 class="card-title">Template Email</h3></div>
    <div class="card-body">

      @php
        $varCommessa = implode(', ', array_map(fn($v) => '{{'.$v.'}}', $this->variabiliCommessa()));
        $varScadenza = implode(', ', array_map(fn($v) => '{{'.$v.'}}', $this->variabiliScadenza()));
      @endphp

      <p class="text-muted small mb-3">
        La prima riga con <code>Oggetto:</code> diventa l'oggetto dell'email. Il resto è il corpo.
      </p>

      @foreach([
        ['template_email_accettazione', 'templateAccettazione', 'Email accettazione commessa', $varCommessa],
        ['template_email_completata',   'templateCompletata',   'Email completamento lavori',  $varCommessa],
        ['template_email_consegnata',   'templateConsegnata',   'Email consegna veicolo',      $varCommessa],
        ['template_email_richiamo_scadenza', 'templateRichiamoScadenza', 'Email richiamo scadenza', $varScadenza],
      ] as [$key, $prop, $label, $vars])
      <div class="form-group">
        <label class="font-weight-bold">{{ $label }}</label>
        <div class="mb-1 small text-muted">Variabili: <code>{{ $vars }}</code></div>
        <textarea class="form-control" rows="6" wire:model="{{ $prop }}" style="font-family:monospace; font-size:0.82rem"></textarea>
      </div>
      @endforeach

    </div>
    <div class="card-footer">
      <button class="btn btn-primary" wire:click="salvaTemplate">
        <i class="fas fa-save mr-1"></i> Salva Template
      </button>
    </div>
  </div>

</div>
