<x-app-layout>
  <x-slot name="title">{{ isset($ordineId) ? 'Ordine #' . $ordineId : 'Nuovo ordine' }}</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('acquisti.ordini') }}">Ordini fornitori</a></li>
    <li class="breadcrumb-item active">{{ isset($ordineId) ? 'Dettaglio' : 'Nuovo' }}</li>
  </x-slot>

  @livewire('acquisti.dettaglio-ordine-fornitore', ['ordineId' => $ordineId ?? null])
</x-app-layout>
