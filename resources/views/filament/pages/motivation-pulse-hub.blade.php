<x-filament::page>
    @php
        $service = app(\App\Services\StudentMotivationService::class);
        $classroom = \App\Models\Classroom::first();
    @endphp
    <div class="filament-pages-page-wrapper space-y-6">
        {{-- Header card with classroom filter and class-level metrics --}}
        <div class="rounded-2xl border border-amber-200/60 bg-gradient-to-br from-amber-50/60 to-white/60 p-6 shadow-sm dark:border-amber-700/30 dark:from-amber-950/30 dark:to-gray-900/40">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-amber-900 dark:text-amber-200 tracking-tight">{{ __('لوحة التميز والتحفيز التربوي') }}</h2>
                    <p class="text-sm text-amber-700/80 dark:text-amber-300/70">{{ __('نبض التميز والتحفيز التربوي — أوسمة ذكية وقياس طاقة القسم') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ __('طاقة تفاعل القسم') }}: {{ $classroom ? ($service ? round(($service->classroomMetrics($classroom)['synergy_percentage'] ?? 0)) : 0) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            {{-- Spotlight cards: Top momentum, top pulse, synergy --}}
            <div class="rounded-2xl border border-amber-200/60 bg-gradient-to-br from-white/80 to-amber-50/40 p-6 shadow-sm dark:border-amber-700/30 dark:from-gray-900/60 dark:to-amber-950/20">
                <h3 class="text-sm font-extrabold text-amber-800 dark:text-amber-200 mb-1">{{ __('بطل قفزة التحدي 🚀') }}</h3>
                <p class="text-xs text-amber-700/70 dark:text-amber-300/60 mb-4">{{ __('أكثر الطلاب تحسناً بين التقييمات') }}</p>
                @php($topM = $service ? $service->topMomentumStudent() : null)
                @if ($topM)
                    <div class="flex items-center gap-3 rounded-xl bg-amber-100/70 dark:bg-amber-950/40 px-4 py-3 border border-amber-200 dark:border-amber-800/40">
                        <span class="text-2xl">🚀</span>
                        <div>
                            <div class="font-extrabold text-amber-900 dark:text-amber-100">{{ $topM['student_name'] ?? __('—') }}</div>
                            <div class="text-[11px] text-amber-700 dark:text-amber-300">{{ $topM['improvement'] ?? '+' }} {{ __('نقطة تحسن') }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-amber-700/60 dark:text-amber-300/50">{{ __('لا توجد بيانات كافية بعد.') }}</div>
                @endif
            </div>

            <div class="rounded-2xl border border-amber-200/60 bg-gradient-to-br from-white/80 to-amber-50/40 p-6 shadow-sm dark:border-amber-700/30 dark:from-gray-900/60 dark:to-amber-950/20">
                <h3 class="text-sm font-extrabold text-amber-800 dark:text-amber-200 mb-1">{{ __('فارس الانضباط 🔥') }}</h3>
                <p class="text-xs text-amber-700/70 dark:text-amber-300/60 mb-4">{{ __('أطول سلسلة حضور متواصلة بدون غياب غير مبرر') }}</p>
                @php($topP = $service ? $service->topPulseStudent() : null)
                @if ($topP)
                    <div class="flex items-center gap-3 rounded-xl bg-amber-100/70 dark:bg-amber-950/40 px-4 py-3 border border-amber-200 dark:border-amber-800/40">
                        <span class="text-2xl">🔥</span>
                        <div>
                            <div class="font-extrabold text-amber-900 dark:text-amber-100">{{ $topP['student_name'] ?? __('—') }}</div>
                            <div class="text-[11px] text-amber-700 dark:text-amber-300">{{ $topP['streak'] ?? 0 }} {{ __('أيام متتالية') }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-amber-700/60 dark:text-amber-300/50">{{ __('لا توجد بيانات كافية بعد.') }}</div>
                @endif
            </div>

            <div class="rounded-2xl border border-amber-200/60 bg-gradient-to-br from-white/80 to-amber-50/40 p-6 shadow-sm dark:border-amber-700/30 dark:from-gray-900/60 dark:to-amber-950/20">
                <h3 class="text-sm font-extrabold text-amber-800 dark:text-amber-200 mb-1">{{ __('نجم المشاركة ⭐') }}</h3>
                <p class="text-xs text-amber-700/70 dark:text-amber-300/60 mb-4">{{ __('أكثر الطلاب حصدًا لإجابات مميزة ومشاركات شجاعة') }}</p>
                @php($topK = $service ? $service->topPositiveKudosStudent() : null)
                @if ($topK)
                    <div class="flex items-center gap-3 rounded-xl bg-amber-100/70 dark:bg-amber-950/40 px-4 py-3 border border-amber-200 dark:border-amber-800/40">
                        <span class="text-2xl">⭐</span>
                        <div>
                            <div class="font-extrabold text-amber-900 dark:text-amber-100">{{ $topK['student_name'] ?? __('—') }}</div>
                            <div class="text-[11px] text-amber-700 dark:text-amber-300">{{ $topK['positive_count'] ?? 0 }} {{ __('ملاحظة إيجابية') }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-amber-700/60 dark:text-amber-300/50">{{ __('لا توجد بيانات كافية بعد.') }}</div>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>
