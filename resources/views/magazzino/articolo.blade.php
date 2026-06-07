<x-app-layout>
  <x-slot name="title">Dettaglio Articolo</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('magazzino.articoli') }}">Articoli</a></li>
    <li class="breadcrumb-item active">Dettaglio</li>
  </x-slot>

  <div class="mb-2">
    <a href="{{ route('magazzino.articoli') }}" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-arrow-left"></i> Torna alla lista
    </a>
  </div>

  @livewire('magazzino.dettaglio-articolo', ['articoloId' => $articoloId])
</x-app-layout>
