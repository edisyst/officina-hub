<x-app-layout>
  <x-slot name="title">Fatture d'acquisto</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Fatture d'acquisto</li>
  </x-slot>

  @livewire('acquisti.lista-fatture-acquisto')
</x-app-layout>
