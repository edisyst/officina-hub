<x-app-layout>
  <x-slot name="title">Ordini fornitori</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Ordini fornitori</li>
  </x-slot>

  @livewire('acquisti.lista-ordini')
</x-app-layout>
