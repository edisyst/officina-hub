<x-app-layout>
  <x-slot name="title">Documento</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('fatturazione.documenti') }}">Documenti</a></li>
    <li class="breadcrumb-item active">Dettaglio</li>
  </x-slot>

  @livewire('fatturazione.dettaglio-documento', ['documentoId' => $documentoId])
</x-app-layout>
