@php
    $platform = \App\Models\PlatformSetting::current();
@endphp

<x-filament-panels::page>
    <!-- Top Developer Status Banner -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/20 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xl ring-4 ring-amber-500/10">
                    <x-heroicon-o-cpu-chip class="w-7 h-7" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $platform->platform_name ?? 'سَجِّل — Sajel' }}
                        </h2>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                            {{ $platform->version_tag ?? 'v3.0.0-LTS' }}
                        </span>
                        @if($platform->isMaintenanceActive())
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300 animate-pulse">
                                {{ __('وضع الصيانة مفعّل') }}
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                {{ __('المنصة تعمل بنشاط') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        {{ $platform->platform_slogan ?? __('المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-stretch md:self-auto justify-end">
                <div class="text-left rtl:text-right text-xs text-gray-500 dark:text-gray-400">
                    <div>{{ __('المطور') }}: <strong class="text-gray-800 dark:text-gray-200">{{ $platform->developer_name }}</strong></div>
                    <div>{{ __('الدعم') }}: <span class="font-mono">{{ $platform->developer_support_email }}</span></div>
                </div>
            </div>
        </div>

        @if($platform->isAnnouncementVisible())
            <div class="mt-4 pt-3 border-t border-amber-500/20 flex items-center gap-2 text-xs text-amber-900 dark:text-amber-200">
                <x-heroicon-m-megaphone class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <span><strong>{{ __('الإعلان النشط') }}:</strong> {{ $platform->system_announcement }}</span>
            </div>
        @endif
    </div>

    <!-- Main Settings Form -->
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-check-badge" color="warning">
                    {{ __('حفظ وتطبيق إعدادات المنصة والمطور') }}
                </x-filament::button>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('تُحدَّث إعدادات المنصة وهوية الشاشات فورياً وتنعكس على جميع مستخدمي النظام.') }}
                </p>
            </div>

            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">
                {{ $platform->footer_copyright_text }}
            </div>
        </div>
    </form>
</x-filament-panels::page>
