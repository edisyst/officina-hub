<x-app-layout title="CRM — Campagne Email">
    <x-slot name="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Campagne Email</li>
    </x-slot>

    @livewire('crm.gestione-campagne')
</x-app-layout>
