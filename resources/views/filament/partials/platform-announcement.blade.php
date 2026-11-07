@php
    $platformSetting = \App\Models\PlatformSetting::current();
@endphp

@if($platformSetting->isMaintenanceActive())
    <div class="px-4 py-2 bg-rose-600 text-white text-xs md:text-sm font-semibold flex items-center justify-between shadow-sm animate-pulse">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <span><strong>{{ __('تنبيه صيانة تقنية') }}:</strong> {{ $platformSetting->maintenance_message ?? __('المنصة في وضع الصيانة المجدولة.') }}</span>
        </div>
        <span class="text-xs bg-rose-700 px-2 py-0.5 rounded font-mono">{{ $platformSetting->version_tag }}</span>
    </div>
@elseif($platformSetting->isAnnouncementVisible())
    @php
        $typeClasses = match($platformSetting->announcement_type) {
            'warning' => 'bg-amber-500 text-gray-900 border-b border-amber-600',
            'danger' => 'bg-rose-500 text-white border-b border-rose-600',
            'success' => 'bg-emerald-600 text-white border-b border-emerald-700',
            default => 'bg-blue-600 text-white border-b border-blue-700',
        };
    @endphp
    <div class="px-4 py-2 {{ $typeClasses }} text-xs md:text-sm font-medium flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.111-1.527-.472a15.71 15.71 0 01-1.31-4.202m2.972 0c.34-1.04.528-2.153.528-3.308 0-1.155-.188-2.268-.528-3.308m0 0a15.72 15.72 0 011.31-4.202c.267-.583.976-.79 1.527-.472l.657.38c.523.301.71.961.463 1.511a14.652 14.652 0 00-.985 2.783m6.666 4.606a3 3 0 00-3.332-2.992M15 12a3 3 0 01-3 3" />
            </svg>
            <span>{{ $platformSetting->system_announcement }}</span>
        </div>
        <span class="text-xs opacity-75 font-mono">{{ $platformSetting->platform_name }}</span>
    </div>
@endif

