<x-app-layout>
  <x-slot name="title">Cambio Stagionale Massivo</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('deposito.index') }}">Deposito Gomme</a></li>
    <li class="breadcrumb-item active">Cambio Stagionale</li>
  </x-slot>

  @livewire('pneumatici.cambio-stagionale-massivo')
</x-app-layout>
