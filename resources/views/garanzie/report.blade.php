<x-app-layout>
  <x-slot name="title">Report Garanzie</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Report Garanzie</li>
  </x-slot>

  <livewire:garanzie.report-garanzie />
</x-app-layout>
