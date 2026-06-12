<x-app-layout>
  <x-slot name="title">Case Madri</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Case Madri</li>
  </x-slot>

  <livewire:impostazioni.gestione-case-madri />
</x-app-layout>
