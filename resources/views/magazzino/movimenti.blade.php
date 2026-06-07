<x-app-layout>
  <x-slot name="title">Movimenti Magazzino</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Movimenti</li>
  </x-slot>

  @livewire('magazzino.report-magazzino', ['tabAttiva' => 'movimenti'])
</x-app-layout>
