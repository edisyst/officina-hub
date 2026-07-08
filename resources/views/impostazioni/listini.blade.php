<x-app-layout>
  <x-slot name="title">Listini e Tariffe</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Listini e Tariffe</li>
  </x-slot>

  <div class="card">
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs" id="listini-tabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link {{ request('tab', 'matrici') === 'matrici' ? 'active' : '' }}"
             href="{{ route('impostazioni.listini') }}?tab=matrici">
            <i class="fas fa-percent mr-1"></i>Matrici Ricambi
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request('tab') === 'tariffe' ? 'active' : '' }}"
             href="{{ route('impostazioni.listini') }}?tab=tariffe">
            <i class="fas fa-clock mr-1"></i>Tariffe Manodopera (orarie)
          </a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
          {{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
          {{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
      @endif

      @if(request('tab', 'matrici') === 'matrici')
        <livewire:impostazioni.matrici-prezzo />
      @else
        <livewire:impostazioni.tariffe-orarie />
      @endif
    </div>
  </div>
</x-app-layout>
