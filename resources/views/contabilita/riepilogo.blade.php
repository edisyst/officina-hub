<x-app-layout>
  <x-slot name="title">Export Commercialista</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Export Commercialista</li>
  </x-slot>

  @livewire('contabilita.riepilogo-commercialista')
</x-app-layout>
