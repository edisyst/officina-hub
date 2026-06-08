<x-app-layout>
  <x-slot name="title">Report Deposito Gomme</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('deposito.index') }}">Deposito Gomme</a></li>
    <li class="breadcrumb-item active">Report</li>
  </x-slot>

  @livewire('pneumatici.report-deposito')
</x-app-layout>
