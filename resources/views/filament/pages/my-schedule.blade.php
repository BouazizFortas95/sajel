<x-filament-panels::page>
    <div class="mb-4">
        <x-filament::tabs :label="__('طرق العرض')">
            <x-filament::tabs.item
                :active="$mode === 'day'"
                wire:click="setMode('day')"
                icon="heroicon-o-squares-2x2"
            >
                {{ __('عرض اليوم') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$mode === 'week'"
                wire:click="setMode('week')"
                icon="heroicon-o-calendar-days"
            >
                {{ __('عرض الأسبوع') }}
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    @if ($mode === 'week')
        @livewire('teacher-timetable')
    @else
        @livewire('teacher-interactive-schedule')
    @endif
</x-filament-panels::page>
