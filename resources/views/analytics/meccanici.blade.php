<x-app-layout>
  <x-slot name="title">Produttività Meccanici</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('analytics.dashboard') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Produttività Meccanici</li>
  </x-slot>

  <livewire:analytics.produttivita-meccanici />
</x-app-layout>
