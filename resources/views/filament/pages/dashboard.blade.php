<x-filament-panels::page class="fi-dashboard-page">
    {{-- The tabbed hub owns all dashboard content, including the quick links
         and the teacher's pending-session queue, so widgets are not rendered
         a second time here. --}}
    @livewire('dashboard-stats')
</x-filament-panels::page>
