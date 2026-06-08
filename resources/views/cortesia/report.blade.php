<x-app-layout>
  <x-slot name="title">Report Cortesia</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cortesia.index') }}">Cortesia</a></li>
    <li class="breadcrumb-item active">Report</li>
  </x-slot>

  @livewire('cortesia.report-cortesia')
</x-app-layout>
