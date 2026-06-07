<x-app-layout>
  <x-slot name="title">Analytics — Pacchetti e Tariffe</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Pacchetti e Tariffe</li>
  </x-slot>

  <livewire:analytics.pacchetti-report />
</x-app-layout>
