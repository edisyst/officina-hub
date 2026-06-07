<x-app-layout>
  <x-slot name="title">Audit Log</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Audit Log</li>
  </x-slot>

  @livewire('admin.audit-log')
</x-app-layout>
