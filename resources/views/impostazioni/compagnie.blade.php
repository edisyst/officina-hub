<x-app-layout title="Compagnie Assicurative">
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Compagnie Assicurative</li>
  </x-slot>

  <livewire:impostazioni.lista-compagnie />
</x-app-layout>
