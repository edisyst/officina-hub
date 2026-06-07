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
