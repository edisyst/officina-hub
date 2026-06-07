<x-app-layout>
  <x-slot name="title">Documenti</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Fatturazione</li>
  </x-slot>

  @livewire('fatturazione.lista-documenti')
</x-app-layout>
