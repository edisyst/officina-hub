<x-app-layout>
  <x-slot name="title">Fornitori</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Fornitori</li>
  </x-slot>

  @livewire('magazzino.lista-fornitori')
</x-app-layout>
