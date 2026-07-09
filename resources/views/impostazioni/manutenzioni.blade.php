<x-app-layout>
  <x-slot name="title">Manutenzioni Ricorrenti</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Manutenzioni Ricorrenti</li>
  </x-slot>

  <livewire:impostazioni.gestione-manutenzioni />
</x-app-layout>
