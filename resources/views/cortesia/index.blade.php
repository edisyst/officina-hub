<x-app-layout>
  <x-slot name="title">Calendario Cortesia</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Veicoli di Cortesia</li>
  </x-slot>

  @livewire('cortesia.calendario-disponibilita')
</x-app-layout>
