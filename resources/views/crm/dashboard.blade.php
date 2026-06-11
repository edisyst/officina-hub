<x-app-layout title="CRM — Dashboard Retention">
    <x-slot name="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">CRM Dashboard</li>
    </x-slot>

    @livewire('crm.dashboard-retention')
</x-app-layout>
