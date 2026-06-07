<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? config('app.name') }} — Officina Hub</title>

  @PwaHead

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

  @livewireStyles
  @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i>
          <span class="ml-1">{{ Auth::user()->name }}</span>
          <span class="badge badge-secondary ml-1">{{ Auth::user()->getRoleNames()->first() }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item">
              <i class="fas fa-sign-out-alt mr-2"></i> Esci
            </button>
          </form>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link text-center">
      <span class="brand-text font-weight-bold">Officina Hub</span>
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="info">
          <span class="d-block text-white small">{{ Auth::user()->name }}</span>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

          <li class="nav-item has-treeview {{ request()->routeIs('dashboard') || request()->routeIs('analytics.*') ? 'menu-open' : '' }}">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') || request()->routeIs('analytics.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Analytics <i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dashboard KPI</p>
                </a>
              </li>
              @role('admin')
              <li class="nav-item">
                <a href="{{ route('analytics.meccanici') }}" class="nav-link {{ request()->routeIs('analytics.meccanici') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Produttività meccanici</p>
                </a>
              </li>
              @endrole
              @hasanyrole('admin|cassa')
              <li class="nav-item">
                <a href="{{ route('analytics.marginalita') }}" class="nav-link {{ request()->routeIs('analytics.marginalita') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Marginalità</p>
                </a>
              </li>
              @endhasanyrole
              @hasanyrole('admin|accettatore|cassa')
              <li class="nav-item">
                <a href="{{ route('analytics.commesse') }}" class="nav-link {{ request()->routeIs('analytics.commesse') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Report commesse</p>
                </a>
              </li>
              @endhasanyrole
              @role('admin')
              <li class="nav-item">
                <a href="{{ route('analytics.pacchetti') }}" class="nav-link {{ request()->routeIs('analytics.pacchetti') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Pacchetti e tariffe</p>
                </a>
              </li>
              @endrole
            </ul>
          </li>

          @canany(['viewAny'], [\App\Models\Cliente::class])
          <li class="nav-item">
            <a href="{{ route('clienti.index') }}" class="nav-link {{ request()->routeIs('clienti.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-users"></i>
              <p>Clienti</p>
            </a>
          </li>
          @endcanany

          @can('viewAny', \App\Models\Veicolo::class)
          <li class="nav-item">
            <a href="{{ route('veicoli.index') }}" class="nav-link {{ request()->routeIs('veicoli.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-car"></i>
              <p>Veicoli</p>
            </a>
          </li>
          @endcan

          @can('viewAny', \App\Models\Commessa::class)
          <li class="nav-item">
            <a href="{{ route('commesse.index') }}" class="nav-link {{ request()->routeIs('commesse.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-clipboard-list"></i>
              <p>
                Commesse
                <span id="badge-commesse" class="badge badge-primary right" style="display:none"></span>
              </p>
            </a>
          </li>
          @endcan

          @can('viewAny', \App\Models\Appuntamento::class)
          <li class="nav-item">
            <a href="{{ route('agenda') }}" class="nav-link {{ request()->routeIs('agenda') ? 'active' : '' }}">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Agenda</p>
            </a>
          </li>
          @endcan

          @hasanyrole('admin|meccanico')
          <li class="nav-item">
            <a href="{{ route('marcatempo') }}" class="nav-link {{ request()->routeIs('marcatempo') ? 'active' : '' }}">
              <i class="nav-icon fas fa-stopwatch"></i>
              <p>Marcatempo</p>
            </a>
          </li>
          @endhasanyrole

          @canany(['viewAny'], [\App\Models\Articolo::class])
          <li class="nav-item has-treeview {{ request()->routeIs('magazzino.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('magazzino.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>
                Magazzino
                <span id="badge-magazzino" class="badge badge-danger right" style="display:none"></span>
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('magazzino.articoli') }}" class="nav-link {{ request()->routeIs('magazzino.articoli*') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Articoli</p>
                </a>
              </li>
              @can('viewAny', \App\Models\Fornitore::class)
              <li class="nav-item">
                <a href="{{ route('magazzino.fornitori') }}" class="nav-link {{ request()->routeIs('magazzino.fornitori') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Fornitori</p>
                </a>
              </li>
              @endcan
              @role('admin')
              <li class="nav-item">
                <a href="{{ route('magazzino.movimenti') }}" class="nav-link {{ request()->routeIs('magazzino.movimenti') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Movimenti</p>
                </a>
              </li>
              @endrole
              @hasanyrole('admin|cassa')
              <li class="nav-item">
                <a href="{{ route('magazzino.report') }}" class="nav-link {{ request()->routeIs('magazzino.report') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Report</p>
                </a>
              </li>
              @endhasanyrole
              @role('admin')
              <li class="nav-item">
                <a href="{{ route('magazzino.categorie') }}" class="nav-link {{ request()->routeIs('magazzino.categorie') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Categorie</p>
                </a>
              </li>
              @endrole
            </ul>
          </li>
          @endcanany

          @hasanyrole('admin|cassa')
          <li class="nav-item has-treeview {{ request()->routeIs('fatturazione.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('fatturazione.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-file-invoice-dollar"></i>
              <p>
                Fatturazione
                <span id="badge-fatturazione" class="badge badge-warning right" style="display:none"></span>
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('fatturazione.documenti') }}" class="nav-link {{ request()->routeIs('fatturazione.documenti*') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Documenti</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('fatturazione.scadenziario') }}" class="nav-link {{ request()->routeIs('fatturazione.scadenziario') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Scadenziario</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('fatturazione.registro-iva') }}" class="nav-link {{ request()->routeIs('fatturazione.registro-iva') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Registro IVA</p>
                </a>
              </li>
            </ul>
          </li>
          @endhasanyrole

          @hasanyrole('admin|accettatore')
          <li class="nav-item">
            <a href="{{ route('scadenziario.index') }}" class="nav-link {{ request()->routeIs('scadenziario.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-calendar-check"></i>
              <p>
                Scadenziario
                <span id="badge-scadenziario" class="badge badge-warning right" style="display:none"></span>
              </p>
            </a>
          </li>
          @endhasanyrole

          @role('admin')
          <li class="nav-item has-treeview {{ request()->routeIs('settings.*') || request()->routeIs('impostazioni.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->routeIs('settings.*') || request()->routeIs('impostazioni.*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-cog"></i>
              <p>Impostazioni <i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Generali</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.ponti') }}" class="nav-link {{ request()->routeIs('impostazioni.ponti') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Ponti</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.checklist') }}" class="nav-link {{ request()->routeIs('impostazioni.checklist') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Checklist</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.email') }}" class="nav-link {{ request()->routeIs('impostazioni.email') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Email</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.compagnie') }}" class="nav-link {{ request()->routeIs('impostazioni.compagnie') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Compagnie Assicur.</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.tariffe') }}" class="nav-link {{ request()->routeIs('impostazioni.tariffe') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Tariffe Manodopera</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('impostazioni.pacchetti') }}" class="nav-link {{ request()->routeIs('impostazioni.pacchetti') ? 'active' : '' }}">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Pacchetti Servizio</p>
                </a>
              </li>
            </ul>
          </li>
          @endrole

        </ul>
      </nav>
    </div>
  </aside>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ $title ?? 'Dashboard' }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              {{ $breadcrumb ?? '' }}
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        @if (session('success'))
          <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
          </div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
          </div>
        @endif

        {{ $slot }}
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>Officina Hub</strong> &copy; {{ date('Y') }}
    <div class="float-right d-none d-sm-inline-block">
      <b>Versione</b> 1.0
    </div>
  </footer>
</div>

<!-- jQuery -->
<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Sortable.js -->
<script src="{{ asset('vendor/adminlte/plugins/sortablejs/Sortable.min.js') }}"></script>
<!-- AdminLTE -->
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
<!-- App JS (Alpine + FullCalendar) -->
@vite(['resources/js/app.js'])

@livewireScripts
@RegisterServiceWorkerScript
@stack('scripts')

<script>
(function() {
  function aggiornaBadgeMenu() {
    fetch('{{ route('api.menu-badges') }}')
      .then(function(r) { return r.ok ? r.json() : null; })
      .then(function(data) {
        if (!data) return;
        aggiornaBadge('badge-commesse', data.commesse_in_lavorazione);
        aggiornaBadge('badge-magazzino', data.articoli_sotto_scorta);
        aggiornaBadge('badge-fatturazione', data.fatture_scadute);
        aggiornaBadge('badge-scadenziario', data.scadenze_imminenti);
      })
      .catch(function() {});
  }

  function aggiornaBadge(id, count) {
    var el = document.getElementById(id);
    if (!el) return;
    if (count > 0) {
      el.textContent = count;
      el.style.display = '';
    } else {
      el.style.display = 'none';
    }
  }

  aggiornaBadgeMenu();
  setInterval(aggiornaBadgeMenu, 120000);
})();
</script>
</body>
</html>
