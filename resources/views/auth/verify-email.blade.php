<x-guest-layout>
  <p class="login-box-msg">Verifica il tuo indirizzo email prima di procedere.</p>

  @if (session('status') == 'verification-link-sent')
  <div class="alert alert-success">Link di verifica inviato.</div>
  @endif

  <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
    @csrf
    <button type="submit" class="btn btn-primary btn-block">Invia email di verifica</button>
  </form>

  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-link btn-block">Esci</button>
  </form>
</x-guest-layout>
