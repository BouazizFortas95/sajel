<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $textbooks = $viewData['textbooks'];
        $classrooms = $viewData['classrooms'];
        $selectedClassroom = $viewData['selectedClassroom'];
        $students = $viewData['students'];
        $kpis = $viewData['kpis'];
        $levels = $viewData['levels'];
        $isAdmin = $viewData['isAdmin'];
    @endphp

    <div class="space-y-6">

        {{-- 1. KPI Summary Cards Strip --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('العناوين المقررة بالمؤسسة') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ $kpis['total_titles'] }}</span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">{{ __('عنواناً معتمداً') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('الرصيد المستلم من الديوان') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ number_format($kpis['total_received']) }}</span>
                    <span class="text-xs text-gray-400">{{ __('نسخة') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('النسخ الموزعة على التلاميذ') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($kpis['total_distributed']) }}</span>
                    <span class="text-xs text-amber-600 font-mono font-bold">{{ number_format($kpis['total_in_stock']) }} {{ __('بالمخزن') }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('حصص التضامن والمجانية') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($kpis['solidarity_count']) }}</span>
                    <span class="text-xs text-emerald-700 dark:text-emerald-300 font-bold font-mono">{{ $kpis['solidarity_rate'] }}%</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('نسبة الاسترجاع وبراءة الذمة') }}</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-primary-600 dark:text-primary-400 font-mono">{{ $kpis['return_rate'] }}%</span>
                    <span class="text-xs text-gray-400">{{ number_format($kpis['total_returned']) }} {{ __('مسترجع') }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Interactive Navigation & Filter Bar --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">

                {{-- Tabs Switcher --}}
                <div class="flex items-center gap-2 border-b md:border-b-0 border-gray-200 dark:border-gray-700 pb-2 md:pb-0">
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'catalog')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'catalog' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📚</span> {{ __('دليل ورصيد الكتب') }} ({{ $textbooks->count() }})
                    </button>

                    <button
                        type="button"
                        wire:click="$set('activeTab', 'distribution')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'distribution' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📤</span> {{ __('توزيع الكتب والتضامن') }}
                    </button>

                    <button
                        type="button"
                        wire:click="$set('activeTab', 'clearance')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'clearance' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📜</span> {{ __('استرجاع الكتب وبراءة الذمة') }}
                    </button>

                    <button
                        type="button"
                        wire:click="$set('activeTab', 'inventory')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $activeTab === 'inventory' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <span>📊</span> {{ __('الجرد السنوي (ONPS)') }}
                    </button>
                </div>

                {{-- Scope Filters --}}
                <div class="flex flex-wrap items-center gap-3">
                    @if ($activeTab === 'catalog')
                        <div class="w-44">
                            <select
                                wire:model.live="selectedLevelId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5"
                            >
                                <option value="">{{ __('كافة المستويات') }}</option>
                                @foreach ($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="w-48">
                            <select
                                wire:model.live="selectedClassroomId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5"
                            >
                                @foreach ($classrooms as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="text-xs font-mono font-bold text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg">
                        {{ $academicYear }}
                    </div>
                </div>

            </div>
        </div>

        {{-- 3. TAB 1: CATALOG & STOCK (دليل ورصيد الكتب المدرسية) --}}
        @if ($activeTab === 'catalog')
            @if ($textbooks->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($textbooks as $tb)
                        @php
                            $dist = $tb->distributedCount($academicYear);
                            $avail = $tb->availableStock($academicYear);
                            $ret = $tb->returnedCount($academicYear);
                            $received = $tb->total_received;
                            $pct = $received > 0 ? min(100, round(($dist / $received) * 100)) : 0;
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                                        {{ $tb->level?->name }}
                                    </span>
                                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white mt-1">
                                        {{ $tb->title }}
                                    </h3>
                                    <p class="text-xs text-gray-500">{{ $tb->subject?->name }}</p>
                                </div>
                                <div class="text-left font-mono">
                                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ number_format((float) $tb->unit_price, 2) }}</span>
                                    <span class="text-[10px] text-gray-400 block">{{ __('دج') }}</span>
                                </div>
                            </div>

                            {{-- Stock Indicator Strip --}}
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg text-xs space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ __('الرصيد المستلم (CRDDP):') }}</span>
                                    <strong class="font-mono text-gray-800 dark:text-gray-200">{{ $received }} {{ __('نسخة') }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ __('الموزع على التلاميذ:') }}</span>
                                    <strong class="font-mono text-emerald-600">{{ $dist }} {{ __('نسخة') }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ __('المتبقي بالمخزن:') }}</span>
                                    <strong class="font-mono text-amber-700 dark:text-amber-400">{{ $avail }} {{ __('نسخة') }}</strong>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div>
                                <div class="flex justify-between text-[11px] font-semibold text-gray-500 mb-1">
                                    <span>{{ __('نسبة استهلاك الحصة:') }}</span>
                                    <span class="font-mono">{{ $pct }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                    <div class="bg-amber-500 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>

                            @if ($isAdmin)
                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                                    <span class="text-[10px] text-gray-400 font-mono">{{ $tb->isbn ?: 'ONPS-STD' }}</span>
                                    <button
                                        type="button"
                                        wire:click="addReceivedStock({{ $tb->id }}, 20)"
                                        class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold text-[10px] rounded transition"
                                    >
                                        ➕ {{ __('إضافة شحنة (+20)') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center text-gray-500">
                    <span class="text-4xl">📚</span>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-2">{{ __('دليل الكتب المدرسية غير مسجل بعد') }}</p>
                    <p class="text-xs text-gray-400 mt-1 mb-4">{{ __('يمكنك النقر على الزر أعلاه لتوليد القائمة الوطنية الرسمية للتعليم المتوسط فورياً.') }}</p>
                </div>
            @endif

        {{-- 4. TAB 2: DISTRIBUTION & SOLIDARITY (توزيع الكتب وحصص التضامن) --}}
        @elseif ($activeTab === 'distribution')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 gap-4">
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">
                            {{ __('سجل توزيع الكتب المدرسية وحصص التضامن') }} — {{ $selectedClassroom?->name }}
                        </h2>
                        <p class="text-xs text-gray-500">{{ __('متابعة تسليم حزم الكتب للتلاميذ وتصنيف المستفيدين من المجانية والتضامن المدرسي') }}</p>
                    </div>

                    @if ($selectedClassroom)
                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('print.textbook.classroom', ['locale' => app()->getLocale(), 'classroom' => $selectedClassroom->id]) }}"
                                target="_blank"
                                class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-bold text-xs rounded-lg flex items-center gap-1.5 transition"
                            >
                                <span>🖨️</span> {{ __('طباعة استمارة متابعة القسم') }}
                            </a>
                        </div>
                    @endif
                </div>

                @if ($students->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                    <th class="p-2.5">#</th>
                                    <th class="p-2.5">{{ __('اسم ولقب التلميذ') }}</th>
                                    <th class="p-2.5">{{ __('رقم التسجيل') }}</th>
                                    <th class="p-2.5">{{ __('الفوج') }}</th>
                                    <th class="p-2.5">{{ __('طبيعة الحصة') }}</th>
                                    <th class="p-2.5">{{ __('الكتب المسلمة') }}</th>
                                    <th class="p-2.5 text-center">{{ __('الإجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($students as $idx => $st)
                                    @php
                                        $loans = $st->loans;
                                        $loanCount = $loans->count();
                                        $isSolidarity = $loans->where('distribution_type', \App\Enums\TextbookDistributionType::Solidarity)->isNotEmpty();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="p-2.5 font-bold">{{ $idx + 1 }}</td>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $st->full_name }}</td>
                                        <td class="p-2.5 font-mono text-gray-500">{{ $st->registration_number ?: '—' }}</td>
                                        <td class="p-2.5 text-gray-500">{{ $st->group?->name ?? '—' }}</td>
                                        <td class="p-2.5">
                                            @if ($isSolidarity)
                                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">
                                                    🌟 {{ __('مجاني (تضامن 5000 دج)') }}
                                                </span>
                                            @elseif ($loanCount > 0)
                                                <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded">
                                                    📖 {{ __('إعارة بيداغوجية') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-[10px]">— {{ __('لم يستلم بعد') }}</span>
                                            @endif
                                        </td>
                                        <td class="p-2.5 font-bold font-mono">
                                            {{ $loanCount }} {{ __('كتب') }}
                                        </td>
                                        <td class="p-2.5 text-center">
                                            @if ($isAdmin)
                                                <button
                                                    type="button"
                                                    wire:click="$set('activeTab', 'clearance')"
                                                    class="text-primary-600 dark:text-primary-400 hover:underline font-bold text-xs"
                                                >
                                                    {{ __('تفاصيل الإعارة والإرجاع') }} →
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 text-xs">
                        {{ __('لا يوجد تلاميذ مسجلون في هذا القسم حالياً.') }}
                    </div>
                @endif
            </div>

        {{-- 5. TAB 3: RETURN & CLEARANCE (استرجاع الكتب وبراءة الذمة) --}}
        @elseif ($activeTab === 'clearance')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 gap-4">
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">
                            {{ __('استرجاع الكتب المدرسية وشهادات براءة الذمة (Quitus)') }} — {{ $selectedClassroom?->name }}
                        </h2>
                        <p class="text-xs text-gray-500">{{ __('فحص سلامة الكتب المعارة، وتوثيق الإرجاع، وإصدار شهادات براءة الذمة للتلاميذ') }}</p>
                    </div>
                </div>

                @if ($students->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($students as $st)
                            @php
                                $status = app(\App\Services\TextbookInventoryService::class)->checkStudentClearance($st, $academicYear);
                                $isCleared = $status['is_cleared'];
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $isCleared ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $isCleared ? '✓' : '⏳' }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">{{ $st->full_name }}</h4>
                                            <p class="text-xs text-gray-500 font-mono">{{ $st->registration_number ?: '—' }} · {{ $st->group?->name }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        {{-- Clearance Badge --}}
                                        @if ($isCleared)
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ✅ {{ __('براءة الذمة مكتملة') }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                ⏳ {{ __('في انتظار استرجاع الكتب') }} ({{ $status['pending_count'] }})
                                            </span>
                                        @endif

                                        {{-- Print Quitus Button --}}
                                        <a
                                            href="{{ route('print.textbook.clearance', ['locale' => app()->getLocale(), 'student' => $st->id]) }}"
                                            target="_blank"
                                            class="px-3 py-1.5 bg-gray-900 text-white font-bold text-xs rounded-lg hover:bg-black transition flex items-center gap-1"
                                        >
                                            <span>📜</span> {{ __('شهادة براءة الذمة') }}
                                        </a>

                                        <a
                                            href="{{ route('print.textbook.clearance.pdf', ['locale' => app()->getLocale(), 'student' => $st->id]) }}"
                                            target="_blank"
                                            class="px-2.5 py-1.5 bg-amber-500 text-slate-950 font-bold text-xs rounded-lg hover:bg-amber-600 transition"
                                            title="PDF"
                                        >
                                            PDF
                                        </a>

                                        @if ($isAdmin && ! $isCleared && $status['total_borrowed'] > 0)
                                            <button
                                                type="button"
                                                wire:click="grantClearance({{ $st->id }})"
                                                wire:confirm="{{ __('هل أنت متأكد من منح براءة الذمة التامة لهذا التلميذ؟') }}"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition"
                                            >
                                                {{ __('تبرئة الذمة يدوياً') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Individual Loans Strip --}}
                                @if ($status['loans']->isNotEmpty())
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 pt-2 border-t border-gray-200 dark:border-gray-700 text-xs">
                                        @foreach ($status['loans'] as $loan)
                                            <div class="p-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                                <div>
                                                    <span class="font-bold text-gray-800 dark:text-gray-200 block truncate max-w-[130px]" title="{{ $loan->textbook?->title }}">
                                                        {{ $loan->textbook?->subject?->name }}
                                                    </span>
                                                    <span class="text-[10px] text-gray-400">
                                                        @if ($loan->returned_at)
                                                            <strong class="text-emerald-600">✓ {{ $loan->return_condition?->label() }}</strong>
                                                        @else
                                                            <span class="text-amber-600">⏳ {{ __('معار') }}</span>
                                                        @endif
                                                    </span>
                                                </div>

                                                @if ($isAdmin && ! $loan->returned_at)
                                                    <button
                                                        type="button"
                                                        wire:click="openReturnModal({{ $loan->id }})"
                                                        class="px-2 py-0.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-[10px] font-bold border border-emerald-200 transition"
                                                    >
                                                        {{ __('إرجاع') }}
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 text-xs">
                        {{ __('لا يوجد تلاميذ في هذا القسم.') }}
                    </div>
                @endif
            </div>

        {{-- 6. TAB 4: ANNUAL ONPS INVENTORY (الجرد السنوي الشامل) --}}
        @elseif ($activeTab === 'inventory')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 gap-4">
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">{{ __('الحصيلة السنوية الشاملة وجرد الكتاب المدرسي (ONPS / CRDDP)') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('الكشف الرسمي المعتمد للإرسال للديوان الوطني للمطبوعات المدرسية ومصلحة المالية والتجهيز') }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('print.textbook.annual-inventory', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                            target="_blank"
                            class="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-bold text-xs rounded-lg flex items-center gap-1.5 transition"
                        >
                            <span>🖨️</span> {{ __('عرض وطباعة الحصيلة كاملة (A4 أفقي)') }}
                        </a>

                        <a
                            href="{{ route('print.textbook.annual-inventory.pdf', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                            target="_blank"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg flex items-center gap-1.5 transition"
                        >
                            <span>📥</span> {{ __('تحميل الحصيلة الرسمية (PDF)') }}
                        </a>
                    </div>
                </div>

                @php
                    $invData = app(\App\Services\TextbookInventoryService::class)->assembleAnnualOnpsInventory($academicYear);
                @endphp

                @if ($invData['rows']->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                    <th class="p-2">#</th>
                                    <th class="p-2">{{ __('الكتاب المقرر') }}</th>
                                    <th class="p-2">{{ __('المستوى') }}</th>
                                    <th class="p-2">{{ __('المستلم') }}</th>
                                    <th class="p-2">{{ __('الموزع') }}</th>
                                    <th class="p-2">{{ __('المجاني') }}</th>
                                    <th class="p-2">{{ __('المسترجع') }}</th>
                                    <th class="p-2">{{ __('المخزن') }}</th>
                                    <th class="p-2">{{ __('السعر') }}</th>
                                    <th class="p-2">{{ __('القيمة المالية') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($invData['rows'] as $i => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="p-2 font-bold">{{ $i + 1 }}</td>
                                        <td class="p-2 font-bold text-gray-900 dark:text-white">{{ $row['textbook']?->title }}</td>
                                        <td class="p-2 text-gray-600 dark:text-gray-400">{{ $row['level'] }}</td>
                                        <td class="p-2 font-mono font-bold">{{ $row['received'] }}</td>
                                        <td class="p-2 font-mono font-bold text-emerald-600">{{ $row['distributed'] }}</td>
                                        <td class="p-2 font-mono text-emerald-700">{{ $row['solidarity'] }}</td>
                                        <td class="p-2 font-mono text-blue-600">{{ $row['returned'] }}</td>
                                        <td class="p-2 font-mono text-amber-700 font-bold">{{ $row['in_stock'] }}</td>
                                        <td class="p-2 font-mono">{{ number_format((float) $row['unit_price'], 2) }}</td>
                                        <td class="p-2 font-mono font-bold">{{ number_format($row['financial_value'], 2) }} دج</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 text-xs">
                        {{ __('لم يتم تسجيل أية بيانات جرد للكتب المدرسية بعد.') }}
                    </div>
                @endif
            </div>
        @endif

    </div>

    {{-- 7. RETURN PROCESS MODAL DIALOG --}}
    @if ($isReturnModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">

                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">
                            {{ __('تسجيل استرجاع كتاب مدرسي') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('فحص الحالة المادية للكتاب قبل منحه براءة الذمة') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeReturnModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block">
                        {{ __('الحالة المادية للكتاب عند الإرجاع:') }}
                    </label>

                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <input type="radio" wire:model="returnCondition" value="good" class="text-emerald-600 focus:ring-emerald-500" />
                            <div>
                                <strong class="text-xs font-bold text-gray-900 dark:text-white block">✅ {{ __('سليم بحالة حسنة') }}</strong>
                                <span class="text-[10px] text-gray-500">{{ __('مستوفٍ للشروط ويمكن إعادة إعارته للتلاميذ في الموسم القادم.') }}</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <input type="radio" wire:model="returnCondition" value="damaged" class="text-amber-600 focus:ring-amber-500" />
                            <div>
                                <strong class="text-xs font-bold text-amber-900 dark:text-amber-300 block">⚠️ {{ __('تالف بحاجة للتعويض') }}</strong>
                                <span class="text-[10px] text-gray-500">{{ __('تلف في الأغلفة أو الصفحات الداخلية يستوجب تسوية مادية.') }}</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <input type="radio" wire:model="returnCondition" value="lost" class="text-rose-600 focus:ring-rose-500" />
                            <div>
                                <strong class="text-xs font-bold text-rose-900 dark:text-rose-300 block">❌ {{ __('مفقود (لم يُرجع)') }}</strong>
                                <span class="text-[10px] text-gray-500">{{ __('ضياع الكتاب ويتوجب تعويضه بالثمن الرسمي كاملاً.') }}</span>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-1 pt-2">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block">
                            {{ __('ملاحظات الفحص (اختياري):') }}
                        </label>
                        <input
                            type="text"
                            wire:model="returnNotes"
                            placeholder="{{ __('مثال: غلاف مُمزق، تم تسديد ثمن التعويض...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeReturnModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>

                    <button
                        type="button"
                        wire:click="confirmReturn"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm transition"
                    >
                        {{ __('تأكيد الإرجاع') }}
                    </button>
                </div>

            </div>
        </div>
    @endif

</x-filament-panels::page>

