<x-app-layout>
  <x-slot name="title">Prima Nota</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Prima Nota</li>
  </x-slot>

  @livewire('contabilita.prima-nota')
</x-app-layout>
