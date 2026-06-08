<x-app-layout>
  <x-slot name="title">Flotta Cortesia</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cortesia.index') }}">Cortesia</a></li>
    <li class="breadcrumb-item active">Flotta</li>
  </x-slot>

  @livewire('cortesia.flotta-cortesia')
</x-app-layout>
