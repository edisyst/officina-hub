<x-guest-layout>
  <p class="login-box-msg">Registra un nuovo account</p>

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="input-group mb-3">
      <input type="text" name="name" value="{{ old('name') }}"
        class="form-control @error('name') is-invalid @enderror"
        placeholder="Nome" required autofocus autocomplete="name">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-user"></span></div>
      </div>
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="input-group mb-3">
      <input type="email" name="email" value="{{ old('email') }}"
        class="form-control @error('email') is-invalid @enderror"
        placeholder="Email" required autocomplete="username">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
      </div>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="input-group mb-3">
      <input type="password" name="password"
        class="form-control @error('password') is-invalid @enderror"
        placeholder="Password" required autocomplete="new-password">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-lock"></span></div>
      </div>
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="input-group mb-3">
      <input type="password" name="password_confirmation"
        class="form-control"
        placeholder="Conferma Password" required autocomplete="new-password">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-lock"></span></div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Registra</button>
  </form>
  <p class="mt-3 mb-1 text-center">
    <a href="{{ route('login') }}">Hai già un account? Accedi</a>
  </p>
</x-guest-layout>
