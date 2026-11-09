<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        @if(!$hasTeacher)
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                <x-filament::icon icon="heroicon-o-user-group" class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">لم يتم تحديد أي أستاذ للعرض</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">يرجى اختيار أستاذ من القائمة لمعاينة كشف وضعيته البيداغوجية.</p>
            </div>
        @else
            <!-- Teacher Selector (For Admin/Leadership) -->
            @if($canSelectOther)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">معاينة وضعية الأستاذ:</span>
                        </div>
                        <div class="w-full sm:w-72">
                            <select
                                wire:model.live="selectedTeacherId"
                                class="w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-sm font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach($teachersList as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->name }} ({{ $t->primarySubject?->name ?? 'بدون مادة' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Teacher Identity & Overall KPI Banner -->
            <div class="relative overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/60 via-white to-slate-50 p-6 shadow-sm dark:border-gray-700 dark:from-gray-800 dark:via-gray-800 dark:to-gray-900">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <!-- Left: Identity -->
                    <div class="flex items-start gap-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-2xl font-extrabold text-white shadow-md shadow-blue-500/20">
                            {{ mb_substr($teacher->name, 0, 1) }}
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black text-gray-900 dark:text-white">
                                    {{ $teacher->name }}
                                </h2>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ $teacher->primarySubject?->name ?? 'غير محدد' }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $academicYear }}
                                </span>
                            </div>

                            <p class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                {{ $school?->school_name ?? 'متوسطة حي 900 مسكن' }} — {{ $school?->directorate_name ?? 'مديرية التربية لولاية المغير' }}
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs font-medium text-gray-600 dark:text-gray-300">
                                <span>الحجم الساعي: <strong>{{ $teacher->weekly_hours_limit ?? 22 }} حصة</strong></span>
                                <span>•</span>
                                <span>تاريخ آخر تحديث: <strong>{{ now()->translatedFormat('d F Y') }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Compliance Gauge Card -->
                    <div class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white/80 p-4 shadow-xs backdrop-blur-xs dark:border-gray-700 dark:bg-gray-800/80">
                        <div class="text-center">
                            <span class="text-3xl font-black text-blue-700 dark:text-blue-400">
                                {{ $overallPercentage }}%
                            </span>
                            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">نسبة الامتثال الكلية</p>
                        </div>
                        <div class="h-10 w-px bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-xs space-y-1">
                            <p class="font-semibold text-gray-700 dark:text-gray-200">
                                الوثائق المؤشرة: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $totalSubmitted }}</span> / {{ $totalItems }}
                            </p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                (المخططات: {{ $submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0) }}/10 • الأعمال: {{ $submittedEvalsCount }}/10)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: المخططات والتدرجات البيداغوجية -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                سجل استلام وتأشير المخططات والتدرجات البيداغوجية
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                المخطط السنوي المعتمد والتدرجات الشهرية من سبتمبر إلى ماي
                            </p>
                        </div>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        نسبة اعتماد المخططات: {{ round((($submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0)) / 10) * 100) }}%
                    </span>
                </div>

                <!-- Annual Plan & Visas -->
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <!-- Annual Plan Status -->
                    <div @class([
                        'rounded-xl border p-4 transition',
                        'border-emerald-200 bg-emerald-50/60 dark:border-emerald-800 dark:bg-emerald-950/20' => $annualPlan['submitted'],
                        'border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-800/50' => !$annualPlan['submitted'],
                    ])>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">المخطط السنوي</span>
                            @if($annualPlan['submitted'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                    <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                                    مُسلّم ومؤشر
                                </span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    بانتظار التسليم
                                </span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-black text-gray-900 dark:text-white">
                            {{ $annualPlan['submitted'] ? 'مؤشر إدارياً' : 'غير مؤشر' }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $annualPlan['date'] ? 'تاريخ الاستلام: ' . $annualPlan['date']->format('Y/m/d') : 'لم يسلم بعد' }}
                        </p>
                    </div>

                    <!-- Censor Visa -->
                    <div @class([
                        'rounded-xl border p-4 transition',
                        'border-blue-200 bg-blue-50/60 dark:border-blue-800 dark:bg-blue-950/20' => $annualPlan['censor_visa'],
                        'border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-800/50' => !$annualPlan['censor_visa'],
                    ])>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">تأشيرة ناظر الدروس</span>
                            @if($annualPlan['censor_visa'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                    <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                                    تم التأشير
                                </span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    قيد الانتظار
                                </span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-black text-gray-900 dark:text-white">
                            {{ $annualPlan['censor_visa'] ? 'مؤشر رسمياً' : 'في انتظار التأشير' }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $annualPlan['censor_visa_at'] ? $annualPlan['censor_visa_at']->format('Y/m/d H:i') : 'لم يؤشر بعد' }}
                        </p>
                    </div>

                    <!-- Director Visa -->
                    <div @class([
                        'rounded-xl border p-4 transition',
                        'border-indigo-200 bg-indigo-50/60 dark:border-indigo-800 dark:bg-indigo-950/20' => $annualPlan['director_visa'],
                        'border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-800/50' => !$annualPlan['director_visa'],
                    ])>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">اعتماد مدير المؤسسة</span>
                            @if($annualPlan['director_visa'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                                    معتمد
                                </span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    قيد الاعتماد
                                </span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-black text-gray-900 dark:text-white">
                            {{ $annualPlan['director_visa'] ? 'معتمد رسمياً' : 'في انتظار الاعتماد' }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $annualPlan['director_visa_at'] ? $annualPlan['director_visa_at']->format('Y/m/d H:i') : 'لم يعتمد بعد' }}
                        </p>
                    </div>
                </div>

                <!-- Monthly Progressions (9 Months Grid) -->
                <div class="mt-5">
                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
                        تدرجات الأشهر الدراسية (المعتمدة: {{ $submittedMonthsCount }} من 9):
                    </p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-9">
                        @foreach($monthlyPlans as $m)
                            <div @class([
                                'rounded-xl border p-2.5 text-center transition',
                                'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800/80 dark:bg-emerald-950/20' => $m['submitted'],
                                'border-gray-200 bg-gray-50/50 dark:border-gray-700/60 dark:bg-gray-800/40 opacity-70' => !$m['submitted'],
                            ])>
                                <p class="text-xs font-black text-gray-900 dark:text-white">
                                    {{ $m['label'] }}
                                </p>
                                <div class="my-1.5 flex justify-center">
                                    @if($m['submitted'])
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                                        </span>
                                    @else
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[10px] font-semibold text-gray-600 dark:text-gray-400" dir="ltr">
                                    {{ $m['submitted'] && $m['date'] ? $m['date']->format('d/m') : '—' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Notes for Plans -->
                @if(!empty($annualPlan['notes']))
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50/70 p-3.5 text-xs text-blue-900 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-200">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" />
                        <div>
                            <strong class="font-bold">ملاحظات الإدارة التربوية حول المخططات:</strong>
                            <p class="mt-0.5 leading-5">{{ $annualPlan['notes'] }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 2: تسليم الأعمال والوثائق التقويمية -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <x-filament::icon icon="heroicon-o-document-check" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                سجل تسليم الأعمال والوثائق التقويمية
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                كشف المستوى، الفروض المحروسة، الواجبات المنزلية، واختبارات الفصول
                            </p>
                        </div>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        الأعمال المسلّمة: {{ $submittedEvalsCount }} من 10 ({{ $evalsPercentage }}%)
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Diagnostic Test -->
                    <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 dark:border-sky-800/60 dark:bg-sky-950/20">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-sky-800 dark:text-sky-300">تقويم تشخيصي</span>
                            @if($evalItems['diagnostic_test']['submitted'])
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">مؤشر</span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-gray-700 dark:text-gray-400">معلق</span>
                            @endif
                        </div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white">كشف المستوى</h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $evalItems['diagnostic_test']['submitted'] && $evalItems['diagnostic_test']['date'] ? 'تاريخ التسليم: ' . $evalItems['diagnostic_test']['date']->format('Y/m/d') : 'لم يسلم بعد' }}
                        </p>
                    </div>

                    <!-- Term 1 -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200">الفصل الأول</span>
                            <span class="text-[11px] font-semibold text-gray-500">
                                {{ ($evalItems['homework_1']['submitted']?1:0) + ($evalItems['quiz_1']['submitted']?1:0) + ($evalItems['exam_1']['submitted']?1:0) }}/3
                            </span>
                        </div>
                        <div class="space-y-2 mt-3">
                            @foreach(['homework_1', 'quiz_1', 'exam_1'] as $k)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $evalItems[$k]['label'] }}</span>
                                    @if($evalItems[$k]['submitted'])
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">✓ مؤشر ({{ $evalItems[$k]['date']?->format('d/m') ?? '' }})</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Term 2 -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200">الفصل الثاني</span>
                            <span class="text-[11px] font-semibold text-gray-500">
                                {{ ($evalItems['homework_2']['submitted']?1:0) + ($evalItems['quiz_2']['submitted']?1:0) + ($evalItems['exam_2']['submitted']?1:0) }}/3
                            </span>
                        </div>
                        <div class="space-y-2 mt-3">
                            @foreach(['homework_2', 'quiz_2', 'exam_2'] as $k)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $evalItems[$k]['label'] }}</span>
                                    @if($evalItems[$k]['submitted'])
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">✓ مؤشر ({{ $evalItems[$k]['date']?->format('d/m') ?? '' }})</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Term 3 -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200">الفصل الثالث</span>
                            <span class="text-[11px] font-semibold text-gray-500">
                                {{ ($evalItems['homework_3']['submitted']?1:0) + ($evalItems['quiz_3']['submitted']?1:0) + ($evalItems['exam_3']['submitted']?1:0) }}/3
                            </span>
                        </div>
                        <div class="space-y-2 mt-3">
                            @foreach(['homework_3', 'quiz_3', 'exam_3'] as $k)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $evalItems[$k]['label'] }}</span>
                                    @if($evalItems[$k]['submitted'])
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">✓ مؤشر ({{ $evalItems[$k]['date']?->format('d/m') ?? '' }})</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Notes for Evaluations -->
                @if(!empty($evalNotes))
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                        <div>
                            <strong class="font-bold">ملاحظات الإدارة التربوية حول الأعمال التقويمية:</strong>
                            <p class="mt-0.5 leading-5">{{ $evalNotes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 3: وتيرة إنجاز المنهاج والتقدم الميداني في التعلمات -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                وتيرة إنجاز المنهاج والتقدم الميداني في التعلمات
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                رصد آلي مباشر من واقع حصص دفتر النصوص والموارد المعرفية المنجزة
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            مزامنة آنية مع دفتر النصوص
                        </span>
                        <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                            نسبة إنجاز المنهاج: {{ $curriculumPercentage ?? 0 }}%
                        </span>
                    </div>
                </div>

                <!-- KPI Cards for Curriculum -->
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-4 dark:border-purple-900/40 dark:bg-purple-950/20">
                        <span class="text-xs font-bold text-purple-900 dark:text-purple-200">نسبة التقدم العامة</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-purple-700 dark:text-purple-300">{{ $curriculumPercentage ?? 0 }}%</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">من إجمالي الموارد</span>
                        </div>
                        <div class="mt-3 w-full bg-purple-200/60 rounded-full h-2 dark:bg-purple-900/50">
                            <div class="bg-purple-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $curriculumPercentage ?? 0) }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200">الموارد المعرفية المنجزة</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $completedCurriculumResources ?? 0 }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">/ {{ $totalCurriculumResources ?? 0 }} موارداً مسجلاً</span>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ ($totalCurriculumResources ?? 0) > 0 ? 'متبقي ' . (($totalCurriculumResources ?? 0) - ($completedCurriculumResources ?? 0)) . ' موارداً خلال الموسم' : 'لا توجد موارد معرفية مسجلة' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200">المقاطع التعليمية المكتملة</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $completedCurriculumSequences ?? 0 }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">/ {{ $totalCurriculumSequences ?? 0 }} مقطعاً بيداغوجياً</span>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                            المقطع يكتمل بإنهاء كافة موارده
                        </p>
                    </div>
                </div>

                <!-- Classes breakdown table if available -->
                @if(isset($curriculumOverview) && $curriculumOverview->isNotEmpty())
                    <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2.5 font-bold">القسم المسند</th>
                                    <th class="px-4 py-2.5 font-bold">المستوى</th>
                                    <th class="px-4 py-2.5 font-bold text-center">الموارد المنجزة</th>
                                    <th class="px-4 py-2.5 font-bold text-center">المقاطع المكتملة</th>
                                    <th class="px-4 py-2.5 font-bold text-center">نسبة الإنجاز</th>
                                    <th class="px-4 py-2.5 font-bold">مؤشر التقدم</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($curriculumOverview as $row)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white">
                                            {{ $row['classroom']->name }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">
                                            {{ $row['level']?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">
                                            {{ $row['completed_resources'] }} / {{ $row['total_resources'] }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-gray-600 dark:text-gray-300">
                                            {{ $row['completed_sequences'] }} / {{ $row['total_sequences'] }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                {{ $row['percentage'] }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 max-w-xs">
                                                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ min(100, $row['percentage']) }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Informational Notice -->
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 shrink-0 text-gray-400" />
                <p>
                    هذا الكشف يُعتبر نسخة إلكترونية مباشرة لسجل المتابعة والتأشير البيداغوجي المعتمد لدى إدارة المؤسسة، ويتم تحديثه آلياً بمجرد قيام مدير المؤسسة أو ناظر الدروس بتأشير استلام وثائقكم.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

