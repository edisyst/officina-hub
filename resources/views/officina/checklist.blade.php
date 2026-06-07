<x-tablet-layout title="Checklist" subtitle="{{ $commessa->numero }}">
  <livewire:checklist.compila-checklist :commessa="$commessa" :template="$template" />
</x-tablet-layout>
