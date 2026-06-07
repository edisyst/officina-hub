<x-app-layout>
  <x-slot name="title">Ponti e Postazioni</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Ponti</li>
  </x-slot>

  <livewire:impostazioni.gestione-ponti />
</x-app-layout>
