@php
    $timerFormatted = $timerFormatted ?? '00:00';
@endphp
<div class="rounded-xl bg-gradient-to-br from-amber-100/50 to-amber-50 dark:from-amber-900/10 dark:to-amber-900/5 border border-amber-200 dark:border-amber-900/20 p-5 shadow-sm space-y-4">
    <h3 class="font-bold text-amber-800 dark:text-amber-200">{{ __('أدوات الحصة التفاعلية') }}</h3>

    {{-- Timer --}}
    <div class="flex items-center gap-3">
        <span class="font-mono text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $timerFormatted }}</span>
        <button wire:click="startTimer(5)" class="text-xs px-2 py-1 rounded bg-amber-500 text-white">{{ __('5 دقائق') }}</button>
        <button wire:click="startTimer(10)" class="text-xs px-2 py-1 rounded bg-amber-600 text-white">{{ __('10 دقائق') }}</button>
        <button wire:click="startTimer(1)" class="text-xs px-2 py-1 rounded bg-amber-700 text-white">{{ __('دقيقة واحدة') }}</button>
        <button wire:click="tick" class="text-xs px-2 py-1 rounded bg-amber-200 dark:bg-amber-900">{{ (isset($timerRunning) && $timerRunning) ? __('إيقاف المؤقت') : __('تشغيل المؤقت') }}</button>
    </div>

    {{-- Random Picker --}}
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('القرعة العشوائية') }}</p>
        <button wire:click="pickRandom" class="px-3 py-1.5 rounded bg-emerald-500 text-white text-sm">{{ __('اختر تلميذ') }}</button>
        <button wire:click="resetPicked" class="px-3 py-1.5 rounded bg-gray-200 dark:bg-gray-700 text-xs">{{ __('إعادة ضبط') }}</button>
        @if ($picked)
            <div class="flex gap-1 mt-2">
                @foreach ($picked as $pid)
                    <span class="text-xs bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded">{{ collect($students)->firstWhere('id', $pid)['name'] ?? $pid }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Auto Groups --}}
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('تكوين المجموعات') }}</p>
        <select wire:model="groupCount" class="text-xs rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 mb-2">
            <option>2</option><option>3</option><option>4</option><option>5</option>
        </select>
        <button wire:click="generateGroups" class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm">{{ __('إنشاء مجموعات') }}</button>
        @if ($groups)
            <div class="flex gap-3 mt-2">
                @foreach ($groups as $g)
                    <div class="text-xs bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded px-2 py-1">
                        <strong>{{ __('مجموعة') }} {{ $loop->iteration }}</strong><br>
                        @foreach ($g as $sid)
                            @php $name = collect($students)->firstWhere('id', $sid)['name'] ?? $sid; @endphp
                            {{ $name }}
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
