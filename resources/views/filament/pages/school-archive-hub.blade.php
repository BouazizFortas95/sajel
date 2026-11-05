<x-filament-panels::page>
    @php
        $classroom = $this->classroom;
        $archiveData = $this->archiveBookData;
        $logs = $archiveData['logs'] ?? collect();
        $curriculumIndex = $archiveData['curriculumIndex'] ?? [];
        $visas = $archiveData['visas'] ?? collect();
        $stats = $archiveData['stats'] ?? [];
        $period = $archiveData['period'] ?? [];
        $locale = app()->getLocale();

        $bookPrintUrl = $classroom ? route('print.school-archive.book', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'subject_id' => $this->subjectId,
            'trimester' => $this->trimester,
            'teacher_id' => $this->teacherId,
        ]) : null;

        $bookPdfUrl = $classroom ? route('print.school-archive.book.pdf', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'subject_id' => $this->subjectId,
            'trimester' => $this->trimester,
            'teacher_id' => $this->teacherId,
        ]) : null;
    @endphp

    <div class="space-y-6">
        {{-- Header & Action Hub --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
                        <x-filament::icon icon="heroicon-o-archive-box" class="w-7 h-7"/>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-gray-950 dark:text-white">
                                {{ __('أرشيف المؤسسة الرقمي ودفاتر النصوص السنوية المجلدة') }}
                            </h2>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $period['name'] ?? __('الموسم الدراسي كاملاً') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('تجميع، مراجعة وتصدير دفاتر النصوص الرسمية ككتب سنوية مجلدة بصيغة A4 مع فهارس المنهاج والتأشيرات الإدارية') }}
                        </p>
                    </div>
                </div>

                {{-- Action Toolbar --}}
                @if ($classroom)
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($bookPrintUrl)
                            <x-filament::button
                                tag="a"
                                href="{{ $bookPrintUrl }}"
                                target="_blank"
                                color="warning"
                                icon="heroicon-o-printer"
                                size="sm"
                            >
                                {{ __('معاينة وطباعة الكتاب السنوي المجلد') }}
                            </x-filament::button>
                        @endif

                        @if ($bookPdfUrl)
                            <x-filament::button
                                tag="a"
                                href="{{ $bookPdfUrl }}"
                                color="gray"
                                icon="heroicon-o-arrow-down-tray"
                                size="sm"
                            >
                                {{ __('تنزيل الكتاب السنوي الكامل PDF') }}
                            </x-filament::button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Cockpit Filters --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-4 lg:items-end">
                {{-- Classroom Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('القسم التربوي') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="classroomId"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="">{{ __('اختر القسم') }}</option>
                            @foreach ($this->classroomOptions as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->level?->name }})</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Subject Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('المادة التعليمية') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="subjectId"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="">{{ __('كافة المواد المقررة') }}</option>
                            @foreach ($this->subjectOptions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Trimester Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الفترة الزمنية') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="trimester"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="">{{ __('الموسم الدراسي كاملاً') }}</option>
                            <option value="1">{{ __('الفصل الأول') }}</option>
                            <option value="2">{{ __('الفصل الثاني') }}</option>
                            <option value="3">{{ __('الفصل الثالث') }}</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Teacher Select (Admins Only) --}}
                @if (auth()->user()?->isAdmin())
                    <div class="grid gap-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الأستاذ المشرف') }}</label>
                        <x-filament::input.wrapper class="bg-transparent border-0">
                            <select
                                wire:model.live="teacherId"
                                class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                            >
                                <option value="">{{ __('جميع الأساتذة') }}</option>
                                @foreach ($this->teacherOptions as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                    </div>
                @else
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 pb-3">
                        <span>{{ __('مؤرشف باسمك كأستاذ المادة') }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if ($classroom)
            {{-- KPI Summary Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- 1. Total Sessions --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-amber-600 dark:text-amber-400 mb-2">
                        <span class="text-xs font-bold">{{ __('الحصص المنجزة') }}</span>
                        <span>📖</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $stats['total_sessions'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('حصة مسجلة ومكتملة') }}
                    </p>
                </div>

                {{-- 2. Instructional Hours --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-sky-600 dark:text-sky-400 mb-2">
                        <span class="text-xs font-bold">{{ __('ساعات التدريس') }}</span>
                        <span>⏱️</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $stats['total_hours'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('ساعة تدريس بيداغوجية') }}
                    </p>
                </div>

                {{-- 3. Covered Resources --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-purple-600 dark:text-purple-400 mb-2">
                        <span class="text-xs font-bold">{{ __('الموارد المعرفية') }}</span>
                        <span>🎯</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $curriculumIndex['taught_resources_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('مورد معرفي منجز') }}
                    </p>
                </div>

                {{-- 4. Syllabus Completion --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 mb-2">
                        <span class="text-xs font-bold">{{ __('نسبة إنجاز المنهاج') }}</span>
                        <span>📊</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $curriculumIndex['completion_rate'] !== null ? $curriculumIndex['completion_rate'].'%' : '—' }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ $this->subjectId ? __('من المقرر الوزاري السنوي') : __('اختر مادة لعرض النسبة') }}
                    </p>
                </div>

                {{-- 5. Administrative Visas --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 mb-2">
                        <span class="text-xs font-bold">{{ __('التأشيرات الإدارية') }}</span>
                        <span>🛡️</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $stats['visas_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('تأشيرة رقابة ومصادقة') }}
                    </p>
                </div>

                {{-- 6. Presence Rate --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400 mb-2">
                        <span class="text-xs font-bold">{{ __('المواظبة العامة') }}</span>
                        <span>👥</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $stats['presence_rate'] ?? 100 }}%
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('متوسط حضور التلاميذ') }}
                    </p>
                </div>
            </div>

            {{-- Curriculum Domains Overview --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 pb-4 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ __('فهرس الميادين والمقاطع التعلمية المنجزة في هذا السجل') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('الميادين المعرفية والمقاطع التعلمية التي تم تدريسها وتوثيقها خلال هذه الفترة') }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300">
                        {{ count($curriculumIndex['domains']) }} {{ __('ميادين مغطاة') }}
                    </span>
                </div>

                @if (!empty($curriculumIndex['domains']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($curriculumIndex['domains'] as $domain)
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-gray-800/30">
                                <h4 class="text-sm font-extrabold text-amber-900 dark:text-amber-300 mb-1">
                                    {{ $domain['name'] }}
                                </h4>
                                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1 mt-2">
                                    <p>• {{ count($domain['sequences']) }} {{ __('مقاطع تعلمية') }}</p>
                                    <p>• {{ $domain['resources_count'] }} {{ __('موارد معرفية منجزة') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center text-sm text-gray-500">
                        {{ __('لم يتم ربط الحصص المسجلة بموارد المنهاج الرسمي بعد.') }}
                    </div>
                @endif
            </div>

            {{-- Recent Lessons Ledger --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ __('سجل الحصص والدروس اليومية المؤرشفة') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('معاينة الحصص المنجزة في هذا القسم') }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                        {{ $logs->count() }} {{ __('حصة منجزة') }}
                    </span>
                </div>

                @if ($logs->isNotEmpty())
                    <div class="divide-y divide-gray-200 dark:divide-white/5 max-h-[600px] overflow-y-auto">
                        @foreach ($logs as $log)
                            <div class="p-4 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">
                                            {{ $log->date?->format('d/m/Y') }}
                                        </span>
                                        @if ($log->schedule?->subject)
                                            <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 px-2 py-0.5 rounded">
                                                {{ $log->schedule->subject->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $log->schedule?->user?->name }}
                                    </div>
                                </div>
                                <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">
                                    {{ $log->title }}
                                </h4>
                                @if ($log->homework)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        📝 {{ __('العمل المنزلي') }}: {{ $log->homework }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-2xl">
                            📚
                        </div>
                        <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">
                            {{ __('لا توجد حصص منجزة ومؤرشفة لهذا القسم بعد') }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __('تأكد من تأكيد إنجاز الحصص اليومية في دفتر النصوص ليتم إدراجها آلياً في الأرشيف السنوي.') }}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                    📚
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    {{ __('يرجى اختيار القسم التربوي لعرض الأرشيف السنوي المجلد') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    {{ __('اختر أحد الأقسام التابعة لك من القائمة أعلاه لعرض الكتاب السنوي الكامل لدفتر النصوص وطباعته أو تنزيله.') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

