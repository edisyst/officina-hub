<x-app-layout>
  <x-slot name="title">Redditività</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('analytics.dashboard') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Redditività</li>
  </x-slot>

  <livewire:reports.profitability />
</x-app-layout>
