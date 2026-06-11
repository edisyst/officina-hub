<x-app-layout>
  <x-slot name="title">Ricezione merce</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('acquisti.ordini') }}">Ordini fornitori</a></li>
    <li class="breadcrumb-item active">Ricezione merce</li>
  </x-slot>

  @livewire('acquisti.ricezione-merce', ['ordineId' => $ordineId])
</x-app-layout>
