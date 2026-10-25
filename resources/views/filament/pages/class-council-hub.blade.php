@php
  $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
  $data = $this->getReportDataProperty();
  $classroom = $data['classroom'] ?? null;
  $kpis = $data['kpis'] ?? [];
  $deliberations = $data['studentDeliberations'] ?? collect();
  $honorDist = $data['honorDistribution'] ?? [];
  $classrooms = $this->getClassroomOptionsProperty();
  $generatedAt = $data['generatedAt'] ?? now();

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';

  $kpiCards = [
      [
          'label' => __('القسم والمستوى'),
          'value' => $classroom?->name ?? '—',
          'hint' => trim(($classroom?->level?->name ?? '—').' · '.($classroom?->academic_year ?? '—')),
          'icon' => 'heroicon-o-academic-cap',
          'tone' => 'amber',
      ],
      [
          'label' => __('عدد التلاميذ'),
          'value' => (string) ($kpis['total_students'] ?? 0),
          'hint' => __('بانتظار العلامات: :count', ['count' => $kpis['pending_students'] ?? 0]),
          'icon' => 'heroicon-o-users',
          'tone' => 'sky',
      ],
      [
          'label' => __('المعدل العام للقسم /20'),
          'value' => ($kpis['class_general_average'] ?? null) !== null
              ? number_format((float) $kpis['class_general_average'], 2)
              : '—',
          'hint' => __('على أساس :count تلميذ مرصود', ['count' => $kpis['graded_students'] ?? 0]),
          'icon' => 'heroicon-o-chart-bar',
          'tone' => 'emerald',
      ],
      [
          'label' => __('نسبة النجاح %'),
          'value' => number_format((float) ($kpis['pass_rate'] ?? 0), 1).'%',
          'hint' => __('من التلاميذ المرصودة علاماتهم'),
          'icon' => 'heroicon-o-check-badge',
          'tone' => 'rose',
      ],
  ];

  $toneClasses = [
      'amber' => [
          'chip' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
          'bar' => 'bg-amber-500',
      ],
      'sky' => [
          'chip' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
          'bar' => 'bg-sky-500',
      ],
      'emerald' => [
          'chip' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
          'bar' => 'bg-emerald-500',
      ],
      'rose' => [
          'chip' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
          'bar' => 'bg-rose-500',
      ],
  ];

  $honorCards = [
      [
          'label' => __('class_council.recommendation.excellence'),
          'count' => $honorDist['excellence'] ?? 0,
          'icon' => 'heroicon-o-trophy',
          'classes' => 'border-emerald-200 bg-emerald-50/80 text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200',
      ],
      [
          'label' => __('class_council.recommendation.encouragement'),
          'count' => $honorDist['encouragement'] ?? 0,
          'icon' => 'heroicon-o-hand-thumb-up',
          'classes' => 'border-sky-200 bg-sky-50/80 text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-200',
      ],
      [
          'label' => __('class_council.recommendation.honor_roll'),
          'count' => $honorDist['honor_roll'] ?? 0,
          'icon' => 'heroicon-o-star',
          'classes' => 'border-amber-200 bg-amber-50/80 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200',
      ],
      [
          'label' => __('يحتاج معالجة بيداغوجية'),
          'count' => $honorDist['warning'] ?? 0,
          'icon' => 'heroicon-o-exclamation-triangle',
          'classes' => 'border-rose-200 bg-rose-50/80 text-rose-900 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200',
      ],
  ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            icon="heroicon-o-users"
            :heading="__('مجلس القسم')"
            :description="__('اختر القسم لعرض المداولات التربوية، النتائج، الحضور، والتوصيات الآلية.')"
        >
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('اختر القسم') }}</span>
                        <x-filament::input.wrapper>
                            <select wire:model.live="classroomId" class="{{ $selectClasses }}">
                                @foreach ($classrooms as $option)
                                    <option value="{{ $option->id }}" class="bg-white text-gray-950 dark:bg-gray-900 dark:text-white">
                                        {{ $option->display_name() }}
                                    </option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                    </label>

                    @if ($classroom)
                        <div class="flex flex-wrap items-center gap-2 pb-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
                                <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                                {{ $classroom->academic_year ?: $schoolSetting->academic_year }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">
                                <x-filament::icon icon="heroicon-m-academic-cap" class="h-4 w-4" />
                                {{ $classroom->level?->name ?? '—' }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    @if ($classroom)
                        <x-filament::button
                            tag="a"
                            :href="$this->getClassroomPrintUrl()"
                            target="_blank"
                            rel="noopener"
                            icon="heroicon-o-printer"
                            color="warning"
                        >
                            {{ __('طباعة محضر مجلس القسم') }}
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            :href="$this->getClassroomPdfUrl()"
                            target="_blank"
                            rel="noopener"
                            icon="heroicon-o-document-arrow-down"
                            color="gray"
                        >
                            {{ __('تحميل محضر مجلس القسم (PDF)') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        @if (! $classroom)
            <x-filament::section>
                <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('لا توجد أقسام متاحة لحسابك حالياً.') }}
                </div>
            </x-filament::section>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpiCards as $card)
                    @php
                        $tone = $toneClasses[$card['tone']];
                    @endphp
                    <div class="relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $tone['bar'] }}"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                                <p class="mt-2 truncate text-2xl font-black tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                                <p class="mt-1 truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
                            </div>
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $tone['chip'] }}">
                                <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('ملخص لوحة الشرف والمعالجة') }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('توزيع التوصيات الآلية على تلاميذ القسم') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                        {{ __('بانتظار العلامات') }}: {{ $honorDist['pending'] ?? 0 }}
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($honorCards as $card)
                        <div class="flex items-center justify-between gap-3 rounded-xl border p-3 {{ $card['classes'] }}">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-white/70 dark:bg-white/10">
                                    <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold">{{ $card['label'] }}</p>
                                    <p class="text-xl font-black">{{ $card['count'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <div>
                        <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('مداولات القسم') }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $classroom->display_name() }} · {{ __('آخر تحديث') }}: {{ $generatedAt->format('Y-m-d H:i') }}
                        </p>
                    </div>
                    <span class="rounded-full bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                        {{ trans_choice(':count تلميذ|:count تلميذ', $deliberations->count(), ['count' => $deliberations->count()]) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-right text-sm">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">{{ __('التلميذ(ة)') }}</th>
                                <th class="px-4 py-3">{{ __('معدلات المواد') }}</th>
                                <th class="px-4 py-3">{{ __('المعدل العام') }}</th>
                                <th class="px-4 py-3">{{ __('الحضور') }}</th>
                                <th class="px-4 py-3">{{ __('السلوك') }}</th>
                                <th class="px-4 py-3">{{ __('التوصية') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($deliberations as $del)
                                @php
                                    $average = $del['generalAverage'];
                                    $averageClasses = $average === null
                                        ? 'bg-gray-100 text-gray-500 ring-gray-300 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10'
                                        : ($average >= 10
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20'
                                            : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20');

                                    $recommendationClasses = match ($del['recommendation_color'] ?? 'gray') {
                                        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
                                        'info' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
                                        'primary' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
                                        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
                                        default => 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
                                    };
                                @endphp

                                <tr class="align-top transition hover:bg-amber-50/40 dark:hover:bg-white/[0.03]">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-950 dark:text-white">{{ $del['full_name'] }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                                {{ $del['group'] ?? __('بدون فوج') }}
                                            </span>
                                            @if ($del['registration_number'])
                                                <span class="text-[10px] font-medium text-gray-400" dir="ltr">{{ $del['registration_number'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex max-w-xs flex-wrap gap-1">
                                            @forelse ($del['perSubjectAverages'] as $subjectAverage)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-white/10 dark:text-slate-200">
                                                    {{ $subjectAverage['subject_name'] }}
                                                    <strong class="text-slate-950 dark:text-white">{{ number_format($subjectAverage['average'], 2) }}</strong>
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex min-w-[92px] items-center justify-center rounded-full px-3 py-1 text-xs font-black ring-1 {{ $averageClasses }}">
                                            {{ $average === null ? __('لم تُرصد بعد') : number_format($average, 2).'/20' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1 text-[10px] font-bold">
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ __('حاضر') }} {{ $del['attendanceCounts']['present'] }}</span>
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ __('غائب') }} {{ $del['attendanceCounts']['absent'] }}</span>
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ __('متأخر') }} {{ $del['attendanceCounts']['late'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1 text-[10px] font-bold">
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">+ {{ $del['behaviorCounts']['positive'] }}</span>
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">− {{ $del['behaviorCounts']['negative'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold ring-1 {{ $recommendationClasses }}">
                                            {{ $del['recommendation'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد بيانات للتلاميذ في هذا القسم') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
