<x-filament-panels::page class="fi-dashboard-page">
    {{-- Developer mode renders the Multi-Institution Aggregator Cockpit,
         while the School Principal, Censor, Teachers, and Staff render
         the institutional operational dashboard. --}}
    @if (auth()->user()?->isDeveloper())
        @livewire('developer-dashboard-stats')
    @else
        @livewire('dashboard-stats')
    @endif
</x-filament-panels::page>
