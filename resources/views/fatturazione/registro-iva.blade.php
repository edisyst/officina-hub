<x-app-layout>
  <x-slot name="title">Registro IVA</x-slot>
  <x-slot name="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Registro IVA</li>
  </x-slot>

  @livewire('fatturazione.registro-iva')
</x-app-layout>
