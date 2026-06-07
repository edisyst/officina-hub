<x-app-layout>
  <x-slot name="title">Report Magazzino</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Report Magazzino</li>
  </x-slot>

  @livewire('magazzino.report-magazzino')
</x-app-layout>
