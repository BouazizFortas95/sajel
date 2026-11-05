@php
    $data = $this->getViewData();
    $metrics = $data['metrics'];
    $velocity = $data['velocity'];
    $subjects = $data['subjects'];
    $levels = $data['levels'];
    $teacherScorecards = $data['teacherScorecards'];
    $reports = $data['reports'];
@endphp

<x-filament-panels::page>
    <div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        {{-- KPI Strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('إجمالي الزيارات التفتيشية') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['total_inspections'] }}</p>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">{{ __('زيارات رسمية مسجلة') }}</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-amber-600 dark:text-amber-400">
                    <x-heroicon-o-document-check class="w-6 h-6" />
                </div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('تغطية الأساتذة بالزيارات') }}</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $metrics['inspected_teachers_count'] }} <span class="text-sm font-normal text-gray-400">/ {{ $metrics['total_teachers_count'] }}</span>
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $metrics['total_teachers_count'] > 0 ? round(($metrics['inspected_teachers_count'] / $metrics['total_teachers_count']) * 100) : 0 }}% {{ __('نسبة المرافقة') }}
                    </p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-emerald-600 dark:text-emerald-400">
                    <x-heroicon-o-user-group class="w-6 h-6" />
                </div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('معدل النقطة التربوية') }}</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" dir="ltr">
                        {{ $metrics['average_score'] !== null ? number_format($metrics['average_score'], 2) : '—' }} <span class="text-xs text-gray-400">/ 20</span>
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('معدل تقييم الأداء العام') }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-academic-cap class="w-6 h-6" />
                </div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('توزيع التقديرات') }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                            {{ $metrics['appreciations']['excellent'] + $metrics['appreciations']['very_good'] }} {{ __('ممتاز/جيد جداً') }}
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300">
                            {{ $metrics['appreciations']['good'] }} {{ __('جيد') }}
                        </span>
                    </div>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl text-purple-600 dark:text-purple-400">
                    <x-heroicon-o-chart-bar class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
            <button
                type="button"
                wire:click="setTab('velocity')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2 {{ $activeTab === 'velocity' ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                <x-heroicon-o-bolt class="w-4 h-4" />
                {{ __('رادار توازي الأقسام والتدرج البيداغوجي') }}
            </button>
            <button
                type="button"
                wire:click="setTab('scorecard')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2 {{ $activeTab === 'scorecard' ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                {{ __('سجل امتثال وتأشيرات الأساتذة') }}
            </button>
            <button
                type="button"
                wire:click="setTab('reports')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2 {{ $activeTab === 'reports' ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                <x-heroicon-o-document-text class="w-4 h-4" />
                {{ __('سجل الزيارات والتقارير الرسمية') }}
                @if (count($reports) > 0)
                    <span class="ms-1 px-1.5 py-0.2 rounded-full text-xs {{ $activeTab === 'reports' ? 'bg-white/30 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                        {{ count($reports) }}
                    </span>
                @endif
            </button>
        </div>

        {{-- TAB 1: Curriculum Velocity & Parallel Section Pacing --}}
        @if ($activeTab === 'velocity')
            <div class="space-y-4">
                {{-- Toolbar Filters --}}
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-wrap items-center gap-4 justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('المادة التعليمية') }}</label>
                            <select wire:model.live="selectedSubjectId" class="text-sm rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5 px-3">
                                @foreach ($subjects as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('المستوى الدراسي') }}</label>
                            <select wire:model.live="selectedLevelId" class="text-sm rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5 px-3">
                                @foreach ($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($velocity && $velocity['total_resources'] > 0)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('إجمالي الموارد المعرفية في المنهاج:') }} <span class="font-bold text-gray-900 dark:text-white">{{ $velocity['total_resources'] }} {{ __('مورداً') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Velocity Radar Cards --}}
                @if ($velocity && count($velocity['classrooms']) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($velocity['classrooms'] as $item)
                            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border {{ $item['pacing_status'] === 'lagging' ? 'border-rose-300 dark:border-rose-800 shadow-rose-100 dark:shadow-none' : 'border-gray-200 dark:border-gray-700' }} shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $item['classroom']->name }}</h3>
                                        @if ($item['pacing_status'] === 'ahead')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
                                                <span>⚡</span> {{ __('متقدم في التدرج') }}
                                            </span>
                                        @elseif ($item['pacing_status'] === 'lagging')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 flex items-center gap-1">
                                                <span>⚠</span> {{ __('متأخر عن باقي الأقسام') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300">
                                                {{ __('متوافق مع التدرج') }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('الأستاذ المكلّف:') }} <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['teacher']?->name ?? __('غير محدد') }}</span>
                                    </p>

                                    <div class="mt-4">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="text-gray-600 dark:text-gray-300">{{ __('الموارد المنجزة:') }} <strong>{{ $item['completed_resources'] }}</strong> / {{ $velocity['total_resources'] }}</span>
                                            <span class="font-bold text-amber-600 dark:text-amber-400" dir="ltr">{{ $item['progress_percent'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                            <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min($item['progress_percent'], 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                @if ($item['resource_gap'] > 0)
                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                        <span>⚠</span> {{ __('فارق التوازي مع القسم الأول:') }} <strong>{{ $item['resource_gap'] }} {{ __('مورد') }}</strong>
                                    </div>
                                @else
                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <span>✓</span> {{ __('القسم في طليعة الإنجاز البيداغوجي') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                        {{ __('لا توجد بيانات تدرج أو أقسام مسجلة لهذا المستوى والمادة.') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 2: Teacher Compliance & Scorecard --}}
        @if ($activeTab === 'scorecard')
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ __('سجل المتابعة البيداغوجية والامتثال للأساتذة') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('متابعة إنجاز دفتر النصوص، التأشيرات الإدارية، المذكرات والزيارات التفتيشية.') }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 text-start">{{ __('الأستاذ(ة)') }}</th>
                                <th class="p-3 text-start">{{ __('المادة') }}</th>
                                <th class="p-3 text-center">{{ __('الدروس المنجزة') }}</th>
                                <th class="p-3 text-center">{{ __('نسبة التأشيرات الإدارية') }}</th>
                                <th class="p-3 text-center">{{ __('المذكرات البيداغوجية') }}</th>
                                <th class="p-3 text-center">{{ __('الزيارات التفتيشية') }}</th>
                                <th class="p-3 text-center">{{ __('معدل النقطة') }}</th>
                                <th class="p-3 text-center">{{ __('آخر زيارة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($teacherScorecards as $sc)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $sc['teacher']->name }}</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $sc['teacher']->primarySubject?->name ?? '—' }}</td>
                                    <td class="p-3 text-center font-bold text-gray-800 dark:text-gray-200">{{ $sc['completed_logs_count'] }}</td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc['visa_rate'] >= 70 ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}" dir="ltr">
                                            {{ $sc['visa_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="p-3 text-center text-gray-700 dark:text-gray-300">{{ $sc['memos_count'] }}</td>
                                    <td class="p-3 text-center font-bold text-blue-600 dark:text-blue-400">{{ $sc['inspections_count'] }}</td>
                                    <td class="p-3 text-center font-bold" dir="ltr">
                                        {{ $sc['average_score'] !== null ? number_format($sc['average_score'], 2) : '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($sc['latest_inspection'])
                                            <span class="px-2 py-0.5 rounded text-[11px] bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300">
                                                {{ $sc['latest_inspection']->visit_date?->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-[11px]">{{ __('لم تسجل زيارة') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-6 text-center text-gray-400">{{ __('لا يوجد أساتذة مسجلون.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: Official Inspection Visit Reports --}}
        @if ($activeTab === 'reports')
            <div class="space-y-4">
                @forelse ($reports as $report)
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                    {{ $report->visit_date?->format('Y-m-d') }}
                                </span>
                                <h4 class="font-bold text-gray-900 dark:text-white text-base">
                                    {{ $report->teacher->name }} — {{ $report->subject?->name }} ({{ $report->classroom?->name }})
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                    {{ $report->appreciation_label }} ({{ $report->pedagogical_score ? number_format($report->pedagogical_score, 2).' / 20' : '—' }})
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                <strong>{{ __('المشرف:') }}</strong> {{ $report->inspector_name }} ({{ $report->inspector_role_label }}) &bull;
                                <strong>{{ __('الموضوع:') }}</strong> {{ $report->lesson_topic }}
                            </p>
                            @if ($report->strengths)
                                <p class="text-xs text-emerald-700 dark:text-emerald-400 line-clamp-1">
                                    <strong>{{ __('نقاط القوة:') }}</strong> {{ $report->strengths }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                            <a
                                href="{{ route('print.inspection-report', ['locale' => app()->getLocale(), 'inspectionReport' => $report]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition"
                            >
                                <x-heroicon-o-printer class="w-4 h-4" />
                                {{ __('طباعة A4') }}
                            </a>
                            <a
                                href="{{ route('print.inspection-report.pdf', ['locale' => app()->getLocale(), 'inspectionReport' => $report]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white transition shadow-sm"
                            >
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                {{ __('تحميل PDF') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                        {{ __('لم يتم تسجيل أي زيارات تفتيشية حتى الآن. انقر على "تسجيل زيارة تفتيشية جديدة" للبدء.') }}
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>

