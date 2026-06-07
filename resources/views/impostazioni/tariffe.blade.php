<x-app-layout>
  <x-slot name="title">Tariffe Manodopera</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Tariffe Manodopera</li>
  </x-slot>

  <livewire:impostazioni.tariffe-manodopera />
</x-app-layout>
