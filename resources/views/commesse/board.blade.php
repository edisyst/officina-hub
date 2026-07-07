<x-app-layout>
  <x-slot name="title">Board Officina</x-slot>
  <livewire:commesse.board />
  @push('scripts')
  @vite(['resources/js/board.js'])
  @endpush
</x-app-layout>
