<x-app-layout>
  <x-slot name="title">Marginalità</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('analytics.dashboard') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Marginalità</li>
  </x-slot>

  <livewire:analytics.marginalita-categorie />
</x-app-layout>
