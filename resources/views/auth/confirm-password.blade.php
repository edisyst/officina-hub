<x-guest-layout>
  <p class="login-box-msg">Area sicura. Conferma la tua password per continuare.</p>

  <form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div class="input-group mb-3">
      <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
        placeholder="Password" required autocomplete="current-password">
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-lock"></span></div>
      </div>
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-block">Conferma</button>
  </form>
</x-guest-layout>
