<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>{{ $title }} — Officina Hub</title>

  @PwaHead

  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- AdminLTE base CSS -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

  <style>
    :root {
      --tablet-font-size: 18px;
      --tablet-btn-height: 56px;
      --header-height: 64px;
    }
    html, body { font-size: var(--tablet-font-size); background: #f4f6f9; }
    /* ---- Header ---- */
    .tablet-header {
      position: fixed; top: 0; left: 0; right: 0; z-index: 1030;
      height: var(--header-height);
      background: #343a40;
      color: #fff;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 20px;
    }
    .tablet-header .brand { font-size: 1.2rem; font-weight: 700; }
    .tablet-header .clock { font-size: 1.4rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .tablet-header .user-info { font-size: 0.9rem; color: #adb5bd; }
    /* ---- Main content ---- */
    .tablet-main {
      margin-top: var(--header-height);
      padding: 20px;
      min-height: calc(100vh - var(--header-height));
    }
    /* ---- Tablet-sized buttons ---- */
    .btn-tablet {
      height: var(--tablet-btn-height);
      min-width: 120px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 8px;
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      touch-action: manipulation;
    }
    /* ---- Cards ---- */
    .card { border-radius: 10px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .card-header { font-size: 1rem; font-weight: 600; padding: 14px 18px; }
    .card-body { padding: 16px 18px; }
    /* ---- Touch-friendly form controls ---- */
    .form-control, .custom-select {
      height: 48px; font-size: var(--tablet-font-size); border-radius: 6px;
    }
    textarea.form-control { height: auto; }
  </style>

  @livewireStyles
  @stack('styles')
</head>
<body>

<header class="tablet-header">
  <div class="brand">
    <i class="fas fa-car-side mr-2"></i>Officina Hub
    @if($subtitle)<small class="ml-2 text-secondary font-weight-normal">— {{ $subtitle }}</small>@endif
  </div>

  <div class="clock" x-data="{ now: '' }" x-init="
    const fmt = () => { const d = new Date(); now = d.toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'}); };
    fmt(); setInterval(fmt, 10000);
  " x-text="now"></div>

  <div class="d-flex align-items-center gap-3">
    <span class="user-info">{{ Auth::user()->name }}</span>
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-th-large"></i>
    </a>
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
      @csrf
      <button type="submit" class="btn btn-sm btn-outline-light">
        <i class="fas fa-sign-out-alt"></i>
      </button>
    </form>
  </div>
</header>

<main class="tablet-main">
  @if (session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  {{ $slot }}
</main>

<!-- jQuery -->
<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- jsQR (QR code scanner) -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<!-- App JS (Alpine) -->
@vite(['resources/js/app.js'])

@livewireScripts
@RegisterServiceWorkerScript
@stack('scripts')
</body>
</html>
