<x-app-layout>
  <x-slot name="title">Gestione Pneumatici — Commessa #{{ $commessaId }}</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('commesse.show', $commessaId) }}">Commessa</a></li>
    <li class="breadcrumb-item active">Pneumatici</li>
  </x-slot>

  @livewire('pneumatici.accettazione-deposito', ['commessaId' => $commessaId])
</x-app-layout>
