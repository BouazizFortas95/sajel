<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $school = $viewData['school'];
        $groupedTeachers = $viewData['groupedTeachers'];
        $subjects = $viewData['subjects'];
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

            <!-- Diagnostic Test Submission -->
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">اختبار كشف المستوى</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $stats['diagnostic_count'] }} / {{ $stats['total_teachers'] }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">نسبة الإيداع: <strong>{{ $stats['diagnostic_percentage'] }}%</strong></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-document-check" class="w-6 h-6" />
                </div>
            </div>

            <!-- Homework 1 Progression -->
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">الواجب المنزلي (الفصل 1)</div>
                    <div class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">
                        {{ $stats['homework_1_count'] }} / {{ $stats['total_teachers'] }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">نسبة الإنجاز: <strong>{{ $stats['homework_1_percentage'] }}%</strong></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/50 flex items-center justify-center text-sky-600 dark:text-sky-400">
                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-6 h-6" />
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
                            صفحة الغلاف + كل مادة في صفحة مستقلة
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('print.evaluation-work-receipts', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubjectId]) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition">
                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                        {{ $selectedSubjectId ? 'طباعة المادة' : 'طباعة جميع المواد (كل مادة في صفحة)' }}
                    </a>

                    <a href="{{ route('print.evaluation-work-receipts.pdf', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubjectId]) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
                        PDF
                    </a>

                    <a href="{{ route('print.evaluation-work-receipts.word', ['locale' => app()->getLocale(), 'subject_id' => $selectedSubjectId, 'layout' => 'separate']) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
                        Word (.docx)
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
                <button wire:click="bulkConfirmDiagnostic"
                        onclick="confirm('هل ترغب في تسجيل استلام اختبار كشف المستوى لجميع الأساتذة المعروضين؟') || event.stopImmediatePropagation()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4" />
                    تأكيد كشف المستوى للجميع
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
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $teachers->count() }} أساتذة)</span>
                        </h3>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        السنة الدراسية: <strong class="text-primary-600 dark:text-primary-400">{{ $academicYear }}</strong>
                    </div>
                </div>

                <!-- 13-Column Ledger Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs border-collapse">
                        <thead>
                            <!-- Header Row 1 -->
                            <tr class="bg-gray-100 dark:bg-gray-800/80 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-300 dark:border-gray-700">
                                <th rowspan="2" class="py-2.5 px-2 text-center border-l border-gray-300 dark:border-gray-700 w-12">#</th>
                                <th rowspan="2" class="py-2.5 px-3 text-right border-l border-gray-300 dark:border-gray-700 min-w-[170px]">اسم ولقب الأستاذ</th>
                                <th rowspan="2" class="py-2.5 px-2 text-center border-l border-gray-300 dark:border-gray-700 min-w-[105px]">اختبار كشف المستوى</th>
                                <th colspan="3" class="py-1.5 px-2 text-center border-l border-gray-300 dark:border-gray-700 bg-blue-50/60 dark:bg-blue-950/30 text-blue-900 dark:text-blue-300">الواجب المنزلي</th>
                                <th colspan="3" class="py-1.5 px-2 text-center border-l border-gray-300 dark:border-gray-700 bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-300">الفروض المحروسة</th>
                                <th colspan="3" class="py-1.5 px-2 text-center border-l border-gray-300 dark:border-gray-700 bg-purple-50/60 dark:bg-purple-950/30 text-purple-900 dark:text-purple-300">الاختبار</th>
                                <th rowspan="2" class="py-2.5 px-3 text-right min-w-[160px]">الملاحظة</th>
                            </tr>
                            <!-- Header Row 2: Sub-columns 1, 2, 3 -->
                            <tr class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold text-[11px] border-b border-gray-300 dark:border-gray-700">
                                <!-- Homework 1, 2, 3 -->
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">1</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">2</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">3</th>
                                <!-- Quizzes 1, 2, 3 -->
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">1</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">2</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">3</th>
                                <!-- Exams 1, 2, 3 -->
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">1</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">2</th>
                                <th class="py-1 px-1.5 text-center border-l border-gray-300 dark:border-gray-700 w-16">3</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($teachers as $teacher)
                                @php
                                    $receipt = $teacher->evaluationWorkReceipts->first();
                                    $rowNumber = $loop->iteration;
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                    <!-- 1. Number -->
                                    <td class="py-2.5 px-2 text-center font-bold text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-800">
                                        {{ $rowNumber }}
                                    </td>

                                    <!-- 2. Teacher Name -->
                                    <td class="py-2.5 px-3 border-l border-gray-200 dark:border-gray-800">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $teacher->name }}</div>
                                        @if ($teacher->secondarySubject)
                                            <span class="inline-block text-[10px] text-sky-600 dark:text-sky-400 mt-0.5">
                                                + {{ $teacher->secondarySubject->name }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 3. Diagnostic Test -->
                                    <td class="py-2 px-1 text-center border-l border-gray-200 dark:border-gray-800 bg-amber-50/30 dark:bg-amber-950/10">
                                        <div class="flex flex-col items-center justify-center gap-1">
                                            <input type="checkbox"
                                                   wire:change="toggleDeliverable({{ $teacher->id }}, 'diagnostic_test', $event.target.checked)"
                                                   @checked($receipt?->diagnostic_test_submitted)
                                                   class="rounded border-gray-300 dark:border-gray-700 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-4 w-4" />
                                            @if($receipt?->diagnostic_test_submitted)
                                                <span class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400">
                                                    {{ $receipt->diagnostic_test_date ? \Carbon\Carbon::parse($receipt->diagnostic_test_date)->format('m/d') : 'مستلم' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 4. Homework (1, 2, 3) -->
                                    @foreach([1, 2, 3] as $term)
                                        @php
                                            $hwKey = "homework_{$term}";
                                            $isHw = $receipt ? $receipt->isItemSubmitted($hwKey) : false;
                                            $hwDate = $receipt ? $receipt->getItemDate($hwKey) : null;
                                        @endphp
                                        <td class="py-2 px-1 text-center border-l border-gray-200 dark:border-gray-800">
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <input type="checkbox"
                                                       wire:change="toggleDeliverable({{ $teacher->id }}, '{{ $hwKey }}', $event.target.checked)"
                                                       @checked($isHw)
                                                       class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500 cursor-pointer h-4 w-4" />
                                                @if($isHw)
                                                    <span class="text-[9px] font-bold text-blue-700 dark:text-blue-400">
                                                        {{ $hwDate ? \Carbon\Carbon::parse($hwDate)->format('m/d') : 'مستلم' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 5. Quizzes (1, 2, 3) -->
                                    @foreach([1, 2, 3] as $term)
                                        @php
                                            $quizKey = "quiz_{$term}";
                                            $isQuiz = $receipt ? $receipt->isItemSubmitted($quizKey) : false;
                                            $quizDate = $receipt ? $receipt->getItemDate($quizKey) : null;
                                        @endphp
                                        <td class="py-2 px-1 text-center border-l border-gray-200 dark:border-gray-800">
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <input type="checkbox"
                                                       wire:change="toggleDeliverable({{ $teacher->id }}, '{{ $quizKey }}', $event.target.checked)"
                                                       @checked($isQuiz)
                                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4" />
                                                @if($isQuiz)
                                                    <span class="text-[9px] font-bold text-indigo-700 dark:text-indigo-400">
                                                        {{ $quizDate ? \Carbon\Carbon::parse($quizDate)->format('m/d') : 'مستلم' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 6. Exams (1, 2, 3) -->
                                    @foreach([1, 2, 3] as $term)
                                        @php
                                            $examKey = "exam_{$term}";
                                            $isExam = $receipt ? $receipt->isItemSubmitted($examKey) : false;
                                            $examDate = $receipt ? $receipt->getItemDate($examKey) : null;
                                        @endphp
                                        <td class="py-2 px-1 text-center border-l border-gray-200 dark:border-gray-800">
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <input type="checkbox"
                                                       wire:change="toggleDeliverable({{ $teacher->id }}, '{{ $examKey }}', $event.target.checked)"
                                                       @checked($isExam)
                                                       class="rounded border-gray-300 dark:border-gray-700 text-purple-600 focus:ring-purple-500 cursor-pointer h-4 w-4" />
                                                @if($isExam)
                                                    <span class="text-[9px] font-bold text-purple-700 dark:text-purple-400">
                                                        {{ $examDate ? \Carbon\Carbon::parse($examDate)->format('m/d') : 'مستلم' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    <!-- 7. Notes -->
                                    <td class="py-2 px-2">
                                        <input type="text"
                                               value="{{ $receipt?->notes ?? '' }}"
                                               wire:change="saveNotes({{ $teacher->id }}, $event.target.value)"
                                               placeholder="ملاحظة..."
                                               class="w-full text-xs rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1 px-2 focus:ring-primary-500 focus:border-primary-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer with Teacher Count -->
                <div class="px-5 py-2.5 bg-gray-50 dark:bg-gray-850 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div>إجمالي أساتذة المادة: <strong>{{ $teachers->count() }}</strong></div>
                    <div>
                        <a href="{{ route('print.evaluation-work-receipts', ['locale' => app()->getLocale(), 'subject_id' => $teachers->first()?->primary_subject_id, 'layout' => 'separate']) }}" target="_blank"
                           class="text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                            <x-filament::icon icon="heroicon-o-printer" class="w-3.5 h-3.5" />
                            طباعة وثيقة هذه المادة
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="w-12 h-12 mx-auto text-gray-400" />
                <h4 class="mt-3 font-bold text-gray-800 dark:text-gray-200">لا توجد سجلات مطابقة للبحث</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">يرجى تعديل خيارات التصفية أو التأكد من إدخال هيئة التدريس.</p>
            </div>
        @endforelse

    </div>
</x-filament-panels::page>

