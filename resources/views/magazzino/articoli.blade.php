<x-app-layout>
  <x-slot name="title">Catalogo Articoli</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Articoli</li>
  </x-slot>

  @livewire('magazzino.lista-articoli')
</x-app-layout>
