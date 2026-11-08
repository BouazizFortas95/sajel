<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $school = $viewData['school'];
        $groupedTeachers = $viewData['groupedTeachers'];
        $subjects = $viewData['subjects'];
        $months = $viewData['months'];
        $stats = $viewData['stats'];
        $academicYear = $viewData['academicYear'];
    @endphp

    <div class="space-y-6">

        <!-- 1. KPI Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Teachers -->
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">هيئة التدريس بالمؤسسة</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $stats['total_teachers'] }} أستاذًا</div>
                    <div class="text-xs text-primary-600 dark:text-primary-400 mt-1">{{ $school->school_name ?? 'متوسطة حي 900 مسكن' }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-users" class="w-6 h-6" />
                </div>
            </div>

            <!-- Annual Plan Submission -->
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">المخطط السنوي المودع</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $stats['annual_submitted'] }} / {{ $stats['total_teachers'] }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">نسبة الإيداع: <strong>{{ $stats['annual_percentage'] }}%</strong></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-document-check" class="w-6 h-6" />
                </div>
            </div>

            <!-- Current Month Progression -->
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">تدرج شهر: {{ $stats['current_month_label'] }}</div>
                    <div class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">
                        {{ $stats['current_month_submitted'] }} / {{ $stats['total_teachers'] }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">نسبة الإنجاز: <strong>{{ $stats['current_month_percentage'] }}%</strong></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/50 flex items-center justify-center text-sky-600 dark:text-sky-400">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6" />
                </div>
            </div>

            <!-- Official Print Preview Action -->
            <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/20 dark:to-orange-950/20 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-amber-800 dark:text-amber-400">الوثيقة النموذجية الرسمية للطباعة</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                        @if ($selectedSubjectId)
                            طباعة الغلاف الرسمي + جدول مادة محددة (صفحتان)
                        @else
                            صفحة الغلاف + كل مادة في صفحة مستقلة (الخيار المقترح)
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubjectId, 'layout' => 'separate']) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition">
                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                        {{ $selectedSubjectId ? 'طباعة المادة (صفحتين)' : 'طباعة (كل مادة في صفحة)' }}
                    </a>

                    @if(! $selectedSubjectId)
                        <a href="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'layout' => 'continuous']) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-xs font-bold transition"
                           title="طباعة جميع الجداول بتدفق مدمج">
                            <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                            طباعة مدمجة
                        </a>
                    @endif

                    <a href="{{ route('print.pedagogical-plan-receipts.pdf', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubjectId, 'layout' => 'separate']) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
                        PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Filters & Search Toolbar -->
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Subject Filter -->
                <div class="w-full sm:w-64">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">تصفية حسب المادة التعليمية</label>
                    <select wire:model.live="selectedSubjectId"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-2 px-3 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">جميع المواد التعليمية ({{ $stats['total_teachers'] }} أستاذًا)</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Teacher Search -->
                <div class="w-full sm:w-64">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">بحث باسم الأستاذ</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="اكتب اسم الأستاذ أو اللقب..."
                           class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-2 px-3 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>

                @if($selectedSubjectId || $search)
                    <button wire:click="$set('selectedSubjectId', null); $set('search', '')"
                            class="text-xs text-red-600 hover:text-red-700 font-semibold mt-5">
                        إعادة التعيين
                    </button>
                @endif
            </div>

            <!-- Quick Bulk Action -->
            <div>
                <button wire:click="bulkConfirmAnnualPlans"
                        onclick="confirm('هل ترغب في تأكيد استلام المخطط السنوي لجميع الأساتذة المعروضين؟') || event.stopImmediatePropagation()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4" />
                    تأكيد المخطط السنوي للجميع
                </button>
            </div>
        </div>

        <!-- 3. Grouped Tables by Subject -->
        @php $tableCounter = 1; @endphp

        @forelse ($groupedTeachers as $subjectName => $teachers)
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <!-- Subject Header -->
                <div class="px-5 py-3 bg-gradient-to-r from-slate-100 to-gray-50 dark:from-gray-800 dark:to-gray-850 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                        <h3 class="font-extrabold text-gray-900 dark:text-white text-sm">
                            مادة {{ $subjectName }}
                        </h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300">
                            {{ $teachers->count() }} أساتذة
                        </span>
                    </div>

                    @php
                        $firstSubjectId = $teachers->first()?->primary_subject_id;
                    @endphp
                    @if ($firstSubjectId)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('print.pedagogical-plan-receipts', ['locale' => app()->getLocale(), 'subject_id' => $firstSubjectId, 'layout' => 'separate']) }}" target="_blank"
                               class="inline-flex items-center gap-1 px-2.5 py-1 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-bold transition shadow-2xs border border-gray-200 dark:border-gray-700">
                                <x-filament::icon icon="heroicon-o-printer" class="w-3.5 h-3.5 text-primary-600" />
                                <span>طباعة مادة {{ $subjectName }} (صفحتين)</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Interactive Ledger Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-bold">
                                <th class="py-2.5 px-3 w-12 text-center">الرقم</th>
                                <th class="py-2.5 px-4 min-w-[180px]">اسم ولقب الأستاذ</th>
                                <th class="py-2.5 px-3 min-w-[120px] text-center bg-amber-50/50 dark:bg-amber-950/20">المخطط السنوي</th>
                                @foreach ($months as $monthKey => $monthLabel)
                                    <th class="py-2.5 px-2 text-center min-w-[70px]">{{ $monthLabel }}</th>
                                @endforeach
                                <th class="py-2.5 px-4 min-w-[160px]">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($teachers as $teacher)
                                @php
                                    $receipt = $teacher->pedagogicalPlanReceipts->first();
                                    $hasAnnual = $receipt && $receipt->annual_plan_submitted;
                                    $annualDate = $receipt?->annual_plan_date ? \Carbon\Carbon::parse($receipt->annual_plan_date)->format('Y-m-d') : '';
                                @endphp
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                                    <!-- Number -->
                                    <td class="py-2.5 px-3 text-center text-gray-500 font-bold">
                                        {{ $tableCounter++ }}
                                    </td>

                                    <!-- Teacher Name & Info -->
                                    <td class="py-2.5 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white text-xs">
                                            {{ $teacher->name }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            <span>{{ $teacher->registration_number ?? 'ENS-2026' }}</span>
                                            @if($teacher->secondarySubject)
                                                <span class="text-primary-600 font-bold">+ {{ $teacher->secondarySubject->name }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Annual Plan Interactive Control -->
                                    <td class="py-2 px-3 text-center bg-amber-50/30 dark:bg-amber-950/10">
                                        <div class="flex flex-col items-center gap-1">
                                            <button type="button"
                                                    wire:click="toggleAnnualPlan({{ $teacher->id }}, {{ $hasAnnual ? 'false' : 'true' }})"
                                                    class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold transition {{ $hasAnnual ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-700' }}">
                                                @if($hasAnnual)
                                                    <x-filament::icon icon="heroicon-s-check" class="w-3.5 h-3.5" />
                                                    مستلم
                                                @else
                                                    <span>□ غير مستلم</span>
                                                @endif
                                            </button>
                                            @if($hasAnnual)
                                                <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-400 font-semibold">
                                                    {{ $annualDate ?: now()->format('Y-m-d') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Monthly Plans (September to May) -->
                                    @foreach ($months as $monthKey => $monthLabel)
                                        @php
                                            $isMonthSub = $receipt ? $receipt->isMonthSubmitted($monthKey) : false;
                                            $monthDate = $receipt ? $receipt->getMonthDate($monthKey) : null;
                                        @endphp
                                        <td class="py-2 px-1 text-center">
                                            <button type="button"
                                                    wire:click="toggleMonthPlan({{ $teacher->id }}, '{{ $monthKey }}', {{ $isMonthSub ? 'false' : 'true' }})"
                                                    title="{{ $monthLabel }}: {{ $isMonthSub ? 'مستلم في ' . $monthDate : 'انقر لتأكيد الاستلام' }}"
                                                    class="w-full py-1 px-1 rounded-md flex flex-col items-center justify-center transition {{ $isMonthSub ? 'bg-sky-50 dark:bg-sky-950/60 border border-sky-300 dark:border-sky-800 text-sky-700 dark:text-sky-300' : 'hover:bg-gray-100 dark:hover:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-700 text-gray-400' }}">
                                                <span class="text-xs font-bold leading-none">
                                                    {{ $isMonthSub ? '✔' : '□' }}
                                                </span>
                                                @if($isMonthSub && $monthDate)
                                                    <span class="text-[9px] font-mono leading-none mt-0.5 opacity-90">
                                                        {{ \Carbon\Carbon::parse($monthDate)->format('d/m') }}
                                                    </span>
                                                @endif
                                            </button>
                                        </td>
                                    @endforeach

                                    <!-- Notes Field -->
                                    <td class="py-2 px-3">
                                        <input type="text"
                                               value="{{ $receipt?->notes ?? '' }}"
                                               wire:change="saveNotes({{ $teacher->id }}, $event.target.value)"
                                               placeholder="ملاحظات..."
                                               class="w-full text-xs py-1 px-2 rounded border border-transparent hover:border-gray-300 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 bg-transparent dark:text-white placeholder:text-gray-400" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="p-12 text-center rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                <x-filament::icon icon="heroicon-o-academic-cap" class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <h4 class="text-base font-bold text-gray-900 dark:text-white">لم يتم العثور على أي أساتذة مطابقين</h4>
                <p class="text-xs text-gray-500 mt-1">يرجى تعديل معايير البحث أو اختيار مادة تعليمية أخرى.</p>
            </div>
        @endforelse

    </div>
</x-filament-panels::page>

