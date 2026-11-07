@php
    $platformSetting = \App\Models\PlatformSetting::current();
@endphp

<footer class="mt-8 py-4 border-t border-gray-200/60 dark:border-gray-800 text-center text-xs text-gray-500 dark:text-gray-400">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 px-6">
        <div>
            {{ $platformSetting->footer_copyright_text ?? 'جميع الحقوق محفوظة © لمنظومة سَجِّل الرقمية' }}
        </div>

        <div class="flex items-center gap-3 font-mono text-[11px]">
            <span>{{ $platformSetting->version_tag ?? 'v3.0.0-LTS' }}</span>
            <span>•</span>
            <a href="{{ $platformSetting->developer_website ?? '#' }}" target="_blank" rel="noopener noreferrer" class="hover:text-amber-600 dark:hover:text-amber-400 transition">
                {{ $platformSetting->developer_name ?? 'فريق التطوير' }}
            </a>
        </div>
    </div>
</footer>

