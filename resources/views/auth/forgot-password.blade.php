<x-guest-layout>
  <p class="login-box-msg">Password dimenticata? Inserisci la tua email.</p>

  @if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="input-group mb-3">
      <input type="email" name="email" value="{{ old('email') }}"
        class="form-control @error('email') is-invalid @enderror"
        placeholder="Email" required autofocus>
      <div class="input-group-append">
        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
      </div>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-block">Invia link di reset</button>
  </form>
  <p class="mt-3 mb-1 text-center">
    <a href="{{ route('login') }}">Torna al login</a>
  </p>
</x-guest-layout>
