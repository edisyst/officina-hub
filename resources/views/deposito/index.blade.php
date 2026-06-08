<x-app-layout>
  <x-slot name="title">Situazione Deposito Gomme</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Deposito Gomme</li>
  </x-slot>

  @livewire('pneumatici.accettazione-deposito')
</x-app-layout>
