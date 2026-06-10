<x-app-layout>
  <x-slot name="title">Impostazioni Officina</x-slot>

  <div class="row">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Dati Officina</h3>
        </div>
        <form action="{{ route('settings.update') }}" method="POST">
          @csrf
          <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="form-group">
              <label>Nome Officina *</label>
              <input type="text" name="officina_nome" value="{{ old('officina_nome', $settings['officina_nome'] ?? '') }}"
                class="form-control @error('officina_nome') is-invalid @enderror" required>
              @error('officina_nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label>Indirizzo</label>
              <input type="text" name="officina_indirizzo" value="{{ old('officina_indirizzo', $settings['officina_indirizzo'] ?? '') }}"
                class="form-control">
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Partita IVA</label>
                  <input type="text" name="officina_piva" value="{{ old('officina_piva', $settings['officina_piva'] ?? '') }}"
                    class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Telefono</label>
                  <input type="text" name="officina_telefono" value="{{ old('officina_telefono', $settings['officina_telefono'] ?? '') }}"
                    class="form-control">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="officina_email" value="{{ old('officina_email', $settings['officina_email'] ?? '') }}"
                class="form-control @error('officina_email') is-invalid @enderror">
              @error('officina_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Costo Orario Manodopera (€)</label>
                  <input type="number" step="0.01" name="costo_orario_default"
                    value="{{ old('costo_orario_default', $settings['costo_orario_default'] ?? '45.00') }}"
                    class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>IVA Default (%)</label>
                  <input type="number" step="0.01" name="iva_default"
                    value="{{ old('iva_default', $settings['iva_default'] ?? '22') }}"
                    class="form-control">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Clausola Preventivo</label>
              <textarea name="clausola_preventivo" rows="4" class="form-control">{{ old('clausola_preventivo', $settings['clausola_preventivo'] ?? '') }}</textarea>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salva Impostazioni
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">

      {{-- Lookup Targa --}}
      <div class="card" x-data="{ mostraApiKey: false }">
        <div class="card-header"><h3 class="card-title">Lookup Targa</h3></div>
        <form action="{{ route('settings.update') }}" method="POST">
          @csrf
          <div class="card-body">
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="lt_abilitato"
                  name="lookup_targa_abilitato" value="1"
                  {{ ($settings['lookup_targa_abilitato'] ?? '0') === '1' ? 'checked' : '' }}>
                <label class="custom-control-label" for="lt_abilitato">Abilita ricerca dati da targa</label>
              </div>
            </div>
            <div class="form-group">
              <label>Provider</label>
              <select class="form-control form-control-sm" name="lookup_targa_provider">
                <option value="mock" {{ ($settings['lookup_targa_provider'] ?? 'mock') === 'mock' ? 'selected' : '' }}>Mock (solo test)</option>
                <option value="infotarga" {{ ($settings['lookup_targa_provider'] ?? '') === 'infotarga' ? 'selected' : '' }}>InfoTarga.com</option>
                <option value="openapi" {{ ($settings['lookup_targa_provider'] ?? '') === 'openapi' ? 'selected' : '' }}>OpenAPI.it</option>
              </select>
            </div>
            <div class="form-group">
              <label>API Key</label>
              <div class="input-group">
                <input :type="mostraApiKey ? 'text' : 'password'" class="form-control form-control-sm"
                  name="lookup_targa_api_key"
                  value="{{ $settings['lookup_targa_api_key'] ?? '' }}"
                  placeholder="Inserisci API key...">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary btn-sm" x-on:click="mostraApiKey = !mostraApiKey">
                    <i :class="mostraApiKey ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Timeout (ms)</label>
              <input type="number" class="form-control form-control-sm" name="lookup_targa_timeout_ms"
                value="{{ $settings['lookup_targa_timeout_ms'] ?? '3000' }}" min="500" max="10000">
            </div>
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="lt_auto"
                  name="lookup_targa_auto_search" value="1"
                  {{ ($settings['lookup_targa_auto_search'] ?? '0') === '1' ? 'checked' : '' }}>
                <label class="custom-control-label" for="lt_auto">Ricerca automatica al blur del campo targa</label>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="fas fa-save mr-1"></i> Salva
            </button>
            <a href="{{ route('impostazioni.lookup-test') }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
              <i class="fas fa-plug mr-1"></i> Test connessione
            </a>
          </div>
        </form>
      </div>

      {{-- Export Contabile --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-calculator mr-1"></i>Export Contabile</h3></div>
        <form action="{{ route('settings.update') }}" method="POST">
          @csrf
          <div class="card-body">
            <div class="alert alert-info py-2 small">
              <i class="fas fa-info-circle mr-1"></i>
              Verificare i codici conto con il proprio commercialista prima del primo export.
            </div>
            <div class="form-group">
              <label>Formato export</label>
              <select class="form-control form-control-sm" name="export_contabile_formato">
                <option value="csv_generico" {{ ($settings['export_contabile_formato'] ?? 'csv_generico') === 'csv_generico' ? 'selected' : '' }}>CSV Generico (Excel)</option>
                <option value="primanota_txt" {{ ($settings['export_contabile_formato'] ?? '') === 'primanota_txt' ? 'selected' : '' }}>Prima Nota TXT</option>
                <option value="teamsystem" {{ ($settings['export_contabile_formato'] ?? '') === 'teamsystem' ? 'selected' : '' }}>TeamSystem Studio</option>
                <option value="zucchetti" {{ ($settings['export_contabile_formato'] ?? '') === 'zucchetti' ? 'selected' : '' }}>Zucchetti Metodo (stub)</option>
                <option value="datagamma" {{ ($settings['export_contabile_formato'] ?? '') === 'datagamma' ? 'selected' : '' }}>Datagamma XML (stub)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Conto vendite</label>
              <input type="text" class="form-control form-control-sm" name="export_contabile_codice_conto_vendite"
                value="{{ $settings['export_contabile_codice_conto_vendite'] ?? '70000' }}">
            </div>
            <div class="form-group">
              <label>Conto IVA vendite</label>
              <input type="text" class="form-control form-control-sm" name="export_contabile_codice_conto_iva_vendite"
                value="{{ $settings['export_contabile_codice_conto_iva_vendite'] ?? '26000' }}">
            </div>
            <div class="form-group">
              <label>Conto clienti</label>
              <input type="text" class="form-control form-control-sm" name="export_contabile_codice_conto_clienti"
                value="{{ $settings['export_contabile_codice_conto_clienti'] ?? '15000' }}">
            </div>
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label>Conto cassa</label>
                  <input type="text" class="form-control form-control-sm" name="export_contabile_codice_conto_cassa"
                    value="{{ $settings['export_contabile_codice_conto_cassa'] ?? '10000' }}">
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label>Conto banca</label>
                  <input type="text" class="form-control form-control-sm" name="export_contabile_codice_conto_banca"
                    value="{{ $settings['export_contabile_codice_conto_banca'] ?? '11000' }}">
                </div>
              </div>
            </div>
            <div class="form-group mb-0">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="conservazione_abilitata"
                  name="conservazione_sostitutiva_abilitata" value="1"
                  {{ ($settings['conservazione_sostitutiva_abilitata'] ?? '0') === '1' ? 'checked' : '' }}>
                <label class="custom-control-label" for="conservazione_abilitata">Conservazione sostitutiva abilitata</label>
              </div>
              <small class="text-muted d-block mt-1">
                La conservazione sostitutiva richiede un servizio accreditato AgID.
                Attivare solo dopo aver configurato il conservatore.
              </small>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="fas fa-save mr-1"></i> Salva
            </button>
            <a href="{{ route('contabilita.riepilogo') }}" class="btn btn-sm btn-outline-secondary ml-1">
              <i class="fas fa-download mr-1"></i> Test export
            </a>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Logo Officina</h3></div>
        <div class="card-body">
          @if(file_exists(public_path('images/logo.png')))
          <img src="{{ asset('images/logo.png') }}?{{ time() }}" alt="Logo" class="img-fluid mb-3">
          @else
          <p class="text-muted">Nessun logo caricato.</p>
          @endif
          <form action="{{ route('settings.logo') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
              <label>Carica nuovo logo (PNG/JPG, max 2MB)</label>
              <input type="file" name="logo" class="form-control-file" accept="image/*">
              @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">
              <i class="fas fa-upload"></i> Carica Logo
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
