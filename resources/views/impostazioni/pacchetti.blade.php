<x-app-layout>
  <x-slot name="title">Pacchetti Servizio</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Pacchetti Servizio</li>
  </x-slot>

  <livewire:impostazioni.pacchetti-servizio />
</x-app-layout>
