<x-app-layout>
  <x-slot name="title">Genera ordini da sottoscorta</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('acquisti.ordini') }}">Ordini fornitori</a></li>
    <li class="breadcrumb-item active">Genera da sottoscorta</li>
  </x-slot>

  @livewire('acquisti.genera-ordini-da-sottoscorta')
</x-app-layout>
