<x-filament-panels::page>
    @php
        $classroom = $this->classroom;
        $awardsData = $this->awardsData;
        $students = $awardsData['students'] ?? collect();
        $allRankedStudents = $awardsData['allRankedStudents'] ?? collect();
        $kpis = $awardsData['kpis'] ?? [];
        $period = $awardsData['period'] ?? [];
        $locale = app()->getLocale();

        $batchPrintUrl = $classroom ? route('print.student-awards.batch', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'trimester' => $this->trimester,
            'group_id' => $this->groupId,
            'award_type' => $this->awardFilter,
        ]) : null;

        $batchPdfUrl = $classroom ? route('print.student-awards.batch.pdf', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'trimester' => $this->trimester,
            'group_id' => $this->groupId,
            'award_type' => $this->awardFilter,
        ]) : null;

        $honorRollPrintUrl = $classroom ? route('print.classroom-honor-roll', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'trimester' => $this->trimester,
            'group_id' => $this->groupId,
        ]) : null;

        $honorRollPdfUrl = $classroom ? route('print.classroom-honor-roll.pdf', [
            'locale' => $locale,
            'classroom' => $classroom->id,
            'trimester' => $this->trimester,
            'group_id' => $this->groupId,
        ]) : null;
    @endphp

    <div class="space-y-6">
        {{-- Header & Action Hub --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="w-7 h-7"/>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-gray-950 dark:text-white">
                                {{ __('لوحة الشرف والشهادات التقديرية والتكريم المدرسي') }}
                            </h2>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $period['name'] ?? __('الفصل الدراسي') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('توليد وطباعة شهادات الامتياز، التشجيع، لوحة الشرف، وحسن السيرة والسلوك آلياً بناءً على مداولات الأساتذة') }}
                        </p>
                    </div>
                </div>

                {{-- Bulk Print & Export Actions --}}
                @if ($classroom)
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($batchPrintUrl)
                            <x-filament::button
                                tag="a"
                                href="{{ $batchPrintUrl }}"
                                target="_blank"
                                color="warning"
                                icon="heroicon-o-printer"
                                size="sm"
                            >
                                {{ __('طباعة جماعية للشهادات (A4)') }}
                            </x-filament::button>
                        @endif

                        @if ($batchPdfUrl)
                            <x-filament::button
                                tag="a"
                                href="{{ $batchPdfUrl }}"
                                color="gray"
                                icon="heroicon-o-arrow-down-tray"
                                size="sm"
                            >
                                {{ __('تنزيل الشهادات PDF') }}
                            </x-filament::button>
                        @endif

                        @if ($honorRollPrintUrl)
                            <x-filament::button
                                tag="a"
                                href="{{ $honorRollPrintUrl }}"
                                target="_blank"
                                color="gray"
                                icon="heroicon-o-document-text"
                                size="sm"
                            >
                                {{ __('لوحة الشرف للإعلانات') }}
                            </x-filament::button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Filters Cockpit --}}
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

                {{-- Trimester Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الفصل الدراسي') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="trimester"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="1">{{ __('الفصل الأول') }}</option>
                            <option value="2">{{ __('الفصل الثاني') }}</option>
                            <option value="3">{{ __('الفصل الثالث') }}</option>
                            <option value="0">{{ __('الموسم الدراسي كاملاً') }}</option>
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Group Select --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('الفوج التربوي') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="groupId"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="">{{ __('القسم كاملاً') }}</option>
                            @foreach ($this->groupOptions as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Award Filter --}}
                <div class="grid gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('نوع الشهادة والتكريم') }}</label>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="awardFilter"
                            class="w-full border border-gray-300 bg-white p-2.5 text-sm text-gray-950 rounded-xl focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white appearance-none"
                        >
                            <option value="">{{ __('جميع التكريمات') }}</option>
                            @foreach (\App\Enums\StudentAwardType::cases() as $awardCase)
                                <option value="{{ $awardCase->value }}">{{ $awardCase->label() }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        @if ($classroom)
            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- 1. Total Honored --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-amber-600 dark:text-amber-400 mb-2">
                        <span class="text-xs font-bold">{{ __('إجمالي المكرمين') }}</span>
                        <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5"/>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $kpis['total_honored'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('من أصل :total تلميذ', ['total' => $kpis['total_students'] ?? 0]) }}
                    </p>
                </div>

                {{-- 2. Excellence --}}
                <div class="bg-white dark:bg-gray-900 border border-amber-300 dark:border-amber-500/30 rounded-2xl p-4 shadow-sm bg-amber-50/20 dark:bg-amber-950/10">
                    <div class="flex items-center justify-between text-amber-700 dark:text-amber-400 mb-2">
                        <span class="text-xs font-bold">{{ __('شهادة امتياز') }}</span>
                        <span>🥇</span>
                    </div>
                    <div class="text-2xl font-black text-amber-950 dark:text-amber-300">
                        {{ $kpis['excellence_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-amber-700/80 dark:text-amber-400/80 mt-1">
                        {{ __('معدل >= 16.0 وسلوك حسن') }}
                    </p>
                </div>

                {{-- 3. Encouragement --}}
                <div class="bg-white dark:bg-gray-900 border border-sky-300 dark:border-sky-500/30 rounded-2xl p-4 shadow-sm bg-sky-50/20 dark:bg-sky-950/10">
                    <div class="flex items-center justify-between text-sky-700 dark:text-sky-400 mb-2">
                        <span class="text-xs font-bold">{{ __('شهادة تشجيع') }}</span>
                        <span>🥈</span>
                    </div>
                    <div class="text-2xl font-black text-sky-950 dark:text-sky-300">
                        {{ $kpis['encouragement_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-sky-700/80 dark:text-sky-400/80 mt-1">
                        {{ __('معدل >= 14.0') }}
                    </p>
                </div>

                {{-- 4. Honor Roll --}}
                <div class="bg-white dark:bg-gray-900 border border-indigo-300 dark:border-indigo-500/30 rounded-2xl p-4 shadow-sm bg-indigo-50/20 dark:bg-indigo-950/10">
                    <div class="flex items-center justify-between text-indigo-700 dark:text-indigo-400 mb-2">
                        <span class="text-xs font-bold">{{ __('لوحة شرف') }}</span>
                        <span>🥉</span>
                    </div>
                    <div class="text-2xl font-black text-indigo-950 dark:text-indigo-300">
                        {{ $kpis['honor_roll_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-indigo-700/80 dark:text-indigo-400/80 mt-1">
                        {{ __('معدل >= 12.0') }}
                    </p>
                </div>

                {{-- 5. Good Conduct --}}
                <div class="bg-white dark:bg-gray-900 border border-emerald-300 dark:border-emerald-500/30 rounded-2xl p-4 shadow-sm bg-emerald-50/20 dark:bg-emerald-950/10">
                    <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-400 mb-2">
                        <span class="text-xs font-bold">{{ __('حسن السيرة والسلوك') }}</span>
                        <span>🏅</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-950 dark:text-emerald-300">
                        {{ $kpis['good_conduct_count'] ?? 0 }}
                    </div>
                    <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400/80 mt-1">
                        {{ __('انضباط تام وصفر غيابات') }}
                    </p>
                </div>

                {{-- 6. Highest Average --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400 mb-2">
                        <span class="text-xs font-bold">{{ __('أعلى معدل بالقسم') }}</span>
                        <span>📈</span>
                    </div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">
                        {{ $kpis['highest_average'] !== null ? number_format($kpis['highest_average'], 2) : '—' }}
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('معدل القسم: :avg', ['avg' => $kpis['class_general_average'] !== null ? number_format($kpis['class_general_average'], 2) : '—']) }}
                    </p>
                </div>
            </div>

            {{-- Students Honor Roster --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ __('قائمة التلاميذ المتفوقين والمكرمين') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('مرتبون حسب المعدل العام مع تفاصيل التكريمات المستحقة وروابط الطباعة المباشرة') }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300">
                        {{ $students->count() }} {{ __('تلميذ معروض') }}
                    </span>
                </div>

                @if ($students->isNotEmpty())
                    <div class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach ($students as $row)
                            @php
                                $student = $row['student'];
                                $rank = $row['rank'];
                                $primaryAward = $row['primary_award'];
                                $rankBadgeClass = match ($rank) {
                                    1 => 'bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-900/40 dark:text-amber-200',
                                    2 => 'bg-slate-200 text-slate-900 border border-slate-300 dark:bg-slate-800 dark:text-slate-200',
                                    3 => 'bg-amber-50 text-amber-900 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp

                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <div class="flex items-center gap-4">
                                    {{-- Rank Badge --}}
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm {{ $rankBadgeClass }} flex-shrink-0">
                                        {{ $rank ?? '—' }}
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-extrabold text-gray-950 dark:text-white">
                                                {{ $row['full_name'] }}
                                            </h4>
                                            @if ($row['group'])
                                                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                                    ({{ $row['group'] }})
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ __('رقم التسجيل') }}: <span class="font-mono font-medium">{{ $row['registration_number'] ?? '—' }}</span>
                                            &nbsp;•&nbsp;
                                            {{ __('الغيابات') }}: <span class="{{ $row['attendance_counts']['absent'] > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 font-semibold' }}">{{ $row['attendance_counts']['absent'] }}</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Middle: Awards & Averages --}}
                                <div class="flex flex-wrap items-center gap-3">
                                    {{-- General Average --}}
                                    <div class="px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-center">
                                        <div class="text-xs text-amber-700 dark:text-amber-400 font-medium">{{ __('المعدل') }}</div>
                                        <div class="text-base font-black text-amber-900 dark:text-amber-200">
                                            {{ $row['general_average'] !== null ? number_format($row['general_average'], 2) : '—' }}
                                        </div>
                                    </div>

                                    {{-- Eligible Award Badges --}}
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($row['eligible_awards'] as $award)
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1
                                                {{ match ($award) {
                                                    \App\Enums\StudentAwardType::Excellence => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300',
                                                    \App\Enums\StudentAwardType::Encouragement => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 border border-sky-300',
                                                    \App\Enums\StudentAwardType::HonorRoll => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-300',
                                                    \App\Enums\StudentAwardType::GoodConduct => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300',
                                                } }}">
                                                <span>{{ match ($award) {
                                                    \App\Enums\StudentAwardType::Excellence => '🥇',
                                                    \App\Enums\StudentAwardType::Encouragement => '🥈',
                                                    \App\Enums\StudentAwardType::HonorRoll => '🥉',
                                                    \App\Enums\StudentAwardType::GoodConduct => '🏅',
                                                } }}</span>
                                                <span>{{ $award->label() }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Individual Print Actions --}}
                                <div class="flex items-center gap-2">
                                    @if ($primaryAward)
                                        <x-filament::button
                                            tag="a"
                                            href="{{ route('print.student-award.single', ['locale' => $locale, 'student' => $student->id, 'awardType' => $primaryAward->value, 'trimester' => $this->trimester]) }}"
                                            target="_blank"
                                            color="gray"
                                            icon="heroicon-o-printer"
                                            size="sm"
                                        >
                                            {{ __('طباعة الشهادة') }}
                                        </x-filament::button>

                                        <x-filament::button
                                            tag="a"
                                            href="{{ route('print.student-award.single.pdf', ['locale' => $locale, 'student' => $student->id, 'awardType' => $primaryAward->value, 'trimester' => $this->trimester]) }}"
                                            color="gray"
                                            icon="heroicon-o-arrow-down-tray"
                                            size="sm"
                                        >
                                            PDF
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-2xl">
                            🎓
                        </div>
                        <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">
                            {{ __('لا توجد تكريمات مطابقة لمعايير البحث الحالية') }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                            {{ __('تأكد من رصد العلامات في دفتر التقييمات لهذا الفصل، أو قم بتغيير عامل التصفية لعرض جميع التلاميذ.') }}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                    🏆
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    {{ __('يرجى اختيار القسم التربوي لعرض لوحة الشرف والشهادات') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    {{ __('قم باختيار أحد الأقسام التابعة لك من القائمة أعلاه لبدء معاينة المتفوقين وطباعة الشهادات التقديرية.') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
