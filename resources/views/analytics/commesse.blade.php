<x-app-layout>
  <x-slot name="title">Report Commesse</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('analytics.dashboard') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Report Commesse</li>
  </x-slot>

  <livewire:analytics.report-commesse />
</x-app-layout>
