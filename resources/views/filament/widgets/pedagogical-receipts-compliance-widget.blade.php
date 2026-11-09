<div class="space-y-4" dir="rtl">
    <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-6 w-6" />
                </span>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        متابعة استلام وتأشير المخططات والأعمال التقويمية
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        مؤشرات الامتثال الإداري والتربوي لهيئة التدريس (إجمالي الأساتذة: {{ $planStats['total_teachers'] }} أستاذ)
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $plansHubUrl }}" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50/70 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300 transition">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                    المخططات والتدرجات
                </a>
                <a href="{{ $evalHubUrl }}" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300 transition">
                    <x-filament::icon icon="heroicon-o-document-check" class="h-4 w-4" />
                    الأعمال التقويمية
                </a>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- البطاقة الأولى: المخططات السنوية والشهرية -->
            <div class="rounded-xl border border-gray-100 bg-slate-50/60 p-4 dark:border-gray-700/60 dark:bg-gray-800/60">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center gap-1.5 font-bold text-sm text-gray-800 dark:text-gray-100">
                        <x-filament::icon icon="heroicon-o-document-chart-bar" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        سجل المخططات والتدرجات البيداغوجية
                    </span>
                    <a href="{{ $plansPdfUrl }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline inline-flex items-center gap-1 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3.5 w-3.5" />
                        تحميل PDF
                    </a>
                </div>

                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-gray-600 dark:text-gray-300">المخطط السنوي:</span>
                            <span class="text-blue-700 dark:text-blue-300 font-bold">
                                {{ $planStats['annual_submitted'] }} / {{ $planStats['total_teachers'] }} ({{ $planStats['annual_percentage'] }}%)
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: {{ $planStats['annual_percentage'] }}%"></div>
                        </div>
                    </div>

                    @if($planStats['current_month_label'])
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-gray-600 dark:text-gray-300">تدرج شهر {{ $planStats['current_month_label'] }}:</span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold">
                                    {{ $planStats['current_month_submitted'] }} / {{ $planStats['total_teachers'] }} ({{ $planStats['current_month_percentage'] }}%)
                                </span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $planStats['current_month_percentage'] }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- البطاقة الثانية: الأعمال التقويمية -->
            <div class="rounded-xl border border-gray-100 bg-slate-50/60 p-4 dark:border-gray-700/60 dark:bg-gray-800/60">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center gap-1.5 font-bold text-sm text-gray-800 dark:text-gray-100">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        سجل تسليم الأعمال التقويمية
                    </span>
                    <a href="{{ $evalPdfUrl }}" target="_blank" class="text-xs font-bold text-amber-600 hover:underline inline-flex items-center gap-1 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3.5 w-3.5" />
                        تحميل PDF
                    </a>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-white p-2.5 shadow-xs border border-gray-100 dark:bg-gray-900 dark:border-gray-700">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">كشف المستوى</p>
                        <p class="text-base font-extrabold text-blue-700 dark:text-blue-400 mt-0.5">{{ $evalStats['diagnostic_percentage'] }}%</p>
                        <p class="text-[10px] text-gray-400">{{ $evalStats['diagnostic_count'] }} أستاذ</p>
                    </div>

                    <div class="rounded-lg bg-white p-2.5 shadow-xs border border-gray-100 dark:bg-gray-900 dark:border-gray-700">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">الفرض 01</p>
                        <p class="text-base font-extrabold text-amber-700 dark:text-amber-400 mt-0.5">{{ $evalStats['quiz_1_percentage'] }}%</p>
                        <p class="text-[10px] text-gray-400">{{ $evalStats['quiz_1_count'] }} أستاذ</p>
                    </div>

                    <div class="rounded-lg bg-white p-2.5 shadow-xs border border-gray-100 dark:bg-gray-900 dark:border-gray-700">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">الواجب 01</p>
                        <p class="text-base font-extrabold text-emerald-700 dark:text-emerald-400 mt-0.5">{{ $evalStats['homework_1_percentage'] }}%</p>
                        <p class="text-[10px] text-gray-400">{{ $evalStats['homework_1_count'] }} أستاذ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

