<x-app-layout>
  <x-slot name="title">Categorie Articoli</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Categorie</li>
  </x-slot>

  @livewire('magazzino.gestione-categorie')
</x-app-layout>
