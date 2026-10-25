<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('حفظ الإعدادات') }}
            </x-filament::button>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('تُحدَّث ذاكرة التخزين المؤقت تلقائياً بعد الحفظ.') }}
            </p>
        </div>
    </form>
</x-filament-panels::page>
