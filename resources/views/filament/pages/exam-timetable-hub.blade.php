<x-filament-panels::page>
    @php
        $classroom = $this->classroom;
        $assessments = $this->assessments;
        $conflicts = $this->conflicts;
        $period = $this->trimesterPeriod;
        $conflictsCount = $this->conflictsCount;
        $totalCount = $this->totalAssessmentsCount;
        $majorCount = $this->majorAssessmentsCount;
        $coveredSubjects = $this->coveredSubjects;
        $printUrl = $this->printUrl;

        // Group assessments by date string
        $assessmentsByDate = $assessments->groupBy(fn ($a) => $a->date?->toDateString() ?? 'unknown');
    @endphp

    <div class="space-y-6">
        {{-- Header & Regulatory Compliance Banner --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-7 h-7"/>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-gray-950 dark:text-white">
                                {{ __('رزنامة الفروض المحروسة والامتحانات الرسمية') }}
                            </h2>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $period['name'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                            <span>{{ __('مخطط التقويم البيداغوجي وفق المناشير الوزارية (الحد الأقصى: فرضين محروسين يومياً للقسم الواحد)') }}</span>
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold">
                                <span>🛡️</span>
                                <span>{{ __('نظام حماية التضارب نشط') }}</span>
                            </span>
                        </p>
                    </div>
                </div>

                {{-- Action Toolbar --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if ($classroom && $printUrl)
                        <x-filament::button
                            tag="a"
                            href="{{ $printUrl }}"
                            target="_blank"
                            color="gray"
                            icon="heroicon-o-printer"
                            size="sm"
                        >
                            {{ __('طباعة الرزنامة الرسمية (A4)') }}
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\AssessmentResource::getUrl('create') }}"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        {{ __('برمجة تقييم جديد') }}
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Cockpit Filters --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-3 lg:items-end">
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

                {{-- Trimester Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الفصل الدراسي') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="trimester"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="1">{{ __('الفصل الأول (سبتمبر - ديسمبر)') }}</option>
                            <option value="2">{{ __('الفصل الثاني (جانفي - مارس)') }}</option>
                            <option value="3">{{ __('الفصل الثالث (أفريل - جوان)') }}</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Assessment Type Filter --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('نوع التقييم') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="assessmentType"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="all">{{ __('جميع التقييمات') }}</option>
                            <option value="{{ \App\Enums\AssessmentType::Midterm->value }}">{{ __('الفروض المحروسة فقط') }}</option>
                            <option value="{{ \App\Enums\AssessmentType::Exam->value }}">{{ __('امتحانات الفصول فقط') }}</option>
                            <option value="{{ \App\Enums\AssessmentType::Quiz->value }}">{{ __('الاختبارات القصيرة والتقويم') }}</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- KPIs Strip --}}
            <div class="mt-6 grid gap-3 grid-cols-2 md:grid-cols-4">
                <div class="rounded-xl border border-stone-200 p-3 text-center dark:border-white/10 bg-stone-50/50 dark:bg-gray-800/30">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ __('إجمالي التقييمات') }}</p>
                    <p class="text-2xl font-black text-gray-950 dark:text-white">{{ $totalCount }}</p>
                </div>

                <div class="rounded-xl border border-amber-200 p-3 text-center dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-900/20">
                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">{{ __('الفروض والامتحانات') }}</p>
                    <p class="text-2xl font-black text-amber-800 dark:text-amber-200">{{ $majorCount }}</p>
                </div>

                <div class="rounded-xl border border-blue-200 p-3 text-center dark:border-blue-500/30 bg-blue-50/50 dark:bg-blue-900/20">
                    <p class="text-[11px] font-bold text-blue-700 dark:text-blue-300">{{ __('المواد المبرمجة') }}</p>
                    <p class="text-2xl font-black text-blue-800 dark:text-blue-200">{{ $coveredSubjects->count() }}</p>
                </div>

                <div class="rounded-xl border p-3 text-center {{ $conflictsCount > 0 ? 'border-rose-300 dark:border-rose-500/40 bg-rose-50/50 dark:bg-rose-900/20' : 'border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-900/20' }}">
                    <p class="text-[11px] font-bold {{ $conflictsCount > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}">{{ __('حالات التضارب') }}</p>
                    <p class="text-2xl font-black {{ $conflictsCount > 0 ? 'text-rose-800 dark:text-rose-200 animate-pulse' : 'text-emerald-800 dark:text-emerald-200' }}">
                        {{ $conflictsCount }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Conflict Warnings Box (if conflicts detected) --}}
        @if ($conflictsCount > 0)
            <div class="p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border-2 border-rose-400 dark:border-rose-800 shadow-sm space-y-3">
                <div class="flex items-center gap-2.5 text-rose-800 dark:text-rose-200">
                    <span class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></span>
                    <h3 class="text-sm font-black">{{ __('تنبيهات تضارب الرزنامة ومخالفة الضوابط الرسمية (:count)', ['count' => $conflictsCount]) }}</h3>
                </div>
                <div class="space-y-2">
                    @foreach ($conflicts as $assessmentId => $clashes)
                        @php
                            $targetAssessment = $assessments->firstWhere('id', $assessmentId);
                        @endphp
                        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-rose-200 dark:border-rose-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-xs text-gray-950 dark:text-white">
                                    {{ $targetAssessment?->title }} — {{ $targetAssessment?->schedule?->subject?->name }} ({{ $targetAssessment?->date?->format('d/m/Y') }})
                                </span>
                                <ul class="mt-1 space-y-0.5 text-xs text-rose-700 dark:text-rose-300">
                                    @foreach ($clashes as $clash)
                                        <li class="flex items-center gap-1.5">
                                            <span>&bull;</span>
                                            <span>{{ $clash['message'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @if ($targetAssessment)
                                <a
                                    href="{{ \App\Filament\Resources\AssessmentResource::getUrl('edit', ['record' => $targetAssessment->id]) }}"
                                    class="text-xs font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 underline shrink-0"
                                >
                                    {{ __('تعديل الموعد') }} &larr;
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Main Timetable Calendar View --}}
        @if ($classroom)
            @if ($assessmentsByDate->isNotEmpty())
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>{{ __('جدول المواعيد المبرمجة لقسم :name', ['name' => $classroom->name]) }}</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                ({{ $period['from'] }} إلى {{ $period['to'] }})
                            </span>
                        </h3>
                    </div>

                    <div class="grid gap-4">
                        @foreach ($assessmentsByDate as $dateString => $dayAssessments)
                            @php
                                $firstAssessment = $dayAssessments->first();
                                $dayCarbon = \Carbon\Carbon::parse($dateString);
                                $majorDayCount = $dayAssessments->filter(fn ($a) => in_array($a->type, [\App\Enums\AssessmentType::Midterm, \App\Enums\AssessmentType::Exam], true))->count();
                                $isOverLimit = $majorDayCount > \App\Services\ExamConflictService::MAX_MAJOR_ASSESSMENTS_PER_DAY;
                            @endphp

                            <div class="rounded-2xl border {{ $isOverLimit ? 'border-rose-400 dark:border-rose-700 bg-rose-50/20' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900' }} p-4 shadow-xs transition">
                                {{-- Date Header --}}
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-gray-100 dark:border-white/5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center font-bold text-xs text-gray-800 dark:text-gray-200 font-mono">
                                            {{ $dayCarbon->format('d') }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-sm text-gray-950 dark:text-white">
                                                {{ $dayCarbon->locale('ar')->translatedFormat('l d F Y') }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2">
                                                ({{ $dayAssessments->count() }} {{ __('تقييمات مبرمجة') }})
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        @if ($isOverLimit)
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500 text-white flex items-center gap-1 shadow-sm">
                                                <span>⚠️</span>
                                                <span>{{ __('تجاوز الحد الوزاري: :count فروض/امتحانات اليوم', ['count' => $majorDayCount]) }}</span>
                                            </span>
                                        @elseif ($majorDayCount === 2)
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                {{ __('الحد الأقصى مكتمل (فرضان اليوم)') }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                {{ __('موعد ملائم ومتاح') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Day Assessments Cards --}}
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($dayAssessments as $assessment)
                                        @php
                                            $hasClash = ! empty($conflicts[$assessment->id]);
                                            $subjectColor = $assessment->schedule?->subject?->color ?? '#64748b';
                                        @endphp
                                        <div class="p-3.5 rounded-xl border {{ $hasClash ? 'border-rose-400 bg-rose-50/40 dark:bg-rose-950/20' : 'border-gray-200 dark:border-white/5 bg-gray-50/50 dark:bg-gray-800/40' }} flex flex-col justify-between gap-3">
                                            <div class="space-y-1.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold text-white shadow-xs"
                                                        style="background-color: {{ $subjectColor }};"
                                                    >
                                                        {{ $assessment->schedule?->subject?->name ?? __('مادة غير محددة') }}
                                                    </span>

                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $assessment->type->color() === 'warning' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : ($assessment->type->color() === 'danger' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300') }}">
                                                        {{ $assessment->type->label() }}
                                                    </span>
                                                </div>

                                                <h4 class="text-sm font-black text-gray-950 dark:text-white">
                                                    {{ $assessment->title }}
                                                </h4>

                                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                                    <p class="flex items-center gap-1 font-mono">
                                                        <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5 text-gray-400"/>
                                                        <span>{{ $assessment->schedule?->start_time?->format('H:i') ?? '—' }} - {{ $assessment->schedule?->end_time?->format('H:i') ?? '—' }}</span>
                                                    </p>
                                                    <p class="flex items-center gap-1">
                                                        <x-filament::icon icon="heroicon-o-user" class="w-3.5 h-3.5 text-gray-400"/>
                                                        <span class="truncate">{{ $assessment->schedule?->user?->name ?? __('الأستاذ') }}</span>
                                                    </p>
                                                    @if ($assessment->schedule?->group)
                                                        <p class="flex items-center gap-1 text-[11px] text-teal-600 dark:text-teal-400">
                                                            <x-filament::icon icon="heroicon-o-user-group" class="w-3 h-3"/>
                                                            <span>{{ $assessment->schedule->group->name }}</span>
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="pt-2 border-t border-gray-200/60 dark:border-white/5 flex items-center justify-between text-xs">
                                                <span class="font-bold text-gray-700 dark:text-gray-300">
                                                    {{ __('العلامة:') }} {{ (float) $assessment->max_score }}
                                                </span>

                                                <div class="flex items-center gap-2">
                                                    <a
                                                        href="{{ \App\Filament\Resources\AssessmentResource::getPrintUrl($assessment) }}"
                                                        target="_blank"
                                                        class="text-gray-500 hover:text-gray-700 dark:hover:text-white p-1"
                                                        title="{{ __('طباعة كشف النقاط') }}"
                                                    >
                                                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4"/>
                                                    </a>
                                                    <a
                                                        href="{{ \App\Filament\Resources\AssessmentResource::getUrl('edit', ['record' => $assessment->id]) }}"
                                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 font-bold p-1"
                                                        title="{{ __('تعديل') }}"
                                                    >
                                                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4"/>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-8 shadow-xs">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-12 w-12 text-primary-500 mx-auto mb-3 opacity-80"/>
                    <h4 class="text-lg font-bold text-gray-950 dark:text-white mb-1">
                        {{ __('لا توجد تقييمات مبرمجة في هذا الفصل للقسم') }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-4">
                        {{ __('لم يتم تسجيل أي فروض أو امتحانات لقسم :classroom خلال :period. يمكنك البدء في برمجة الفروض الآن.', ['classroom' => $classroom->name, 'period' => $period['name']]) }}
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\AssessmentResource::getUrl('create') }}"
                        icon="heroicon-o-plus"
                        size="sm"
                    >
                        {{ __('برمجة فرض أو امتحان جديد') }}
                    </x-filament::button>
                </div>
            @endif
        @else
            <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-8 shadow-xs">
                <x-filament::icon icon="heroicon-o-academic-cap" class="h-12 w-12 text-gray-400 mx-auto mb-3 opacity-80"/>
                <h4 class="text-lg font-bold text-gray-950 dark:text-white mb-1">{{ __('يرجى اختيار قسم لعرض رزنامة الفروض') }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    {{ __('اختر قسماً وفصلاً دراسياً من القوائم أعلاه لمعاينة مخطط الفروض، كشف التضارب الزمني، وطباعة الجدول الرسمي.') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
