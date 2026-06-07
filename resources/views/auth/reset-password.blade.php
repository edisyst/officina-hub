<x-guest-layout>
  <p class="login-box-msg">Reimposta la tua password</p>

  <form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="input-group mb-3">
      <input type="email" name="email" value="{{ old('email', $request->email) }}"
        class="form-control @error('email') is-invalid @enderror"
        placeholder="Email" required autofocus autocomplete="username">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
      </div>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="input-group mb-3">
      <input type="password" name="password"
        class="form-control @error('password') is-invalid @enderror"
        placeholder="Nuova Password" required autocomplete="new-password">
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

    <button type="submit" class="btn btn-primary btn-block">Reimposta Password</button>
  </form>
</x-guest-layout>
