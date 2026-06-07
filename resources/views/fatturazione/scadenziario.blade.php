<x-app-layout>
  <x-slot name="title">Scadenziario incassi</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Scadenziario</li>
  </x-slot>

  @livewire('fatturazione.scadenziario')
</x-app-layout>
