<x-app-layout>
  <x-slot name="title">Export XML SDI Batch</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Export XML SDI</li>
  </x-slot>

  @livewire('contabilita.export-sdi-batch')
</x-app-layout>
