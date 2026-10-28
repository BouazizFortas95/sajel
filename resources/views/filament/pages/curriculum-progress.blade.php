<x-filament-panels::page>
    @php
        $overview = $this->overview;
        $scopedOverview = $this->scopedOverview;
        $filteredOverview = $this->filteredOverview;
        $paginator = $this->overviewPaginator;
        $stats = $this->stats;
        $detail = $this->detail;
        $subjectOptions = $this->subjectOptions;
        $classroomOptions = $this->classroomOptions;
        $statusFilterOptions = $this->statusFilterOptions;
        $sortOptions = $this->sortOptions;
        $perPageOptions = $this->perPageOptions;
        $statusLabels = [
            'completed' => __('مكتمل'),
            'in_progress' => __('قيد الإنجاز'),
            'not_started' => __('لم يبدأ'),
        ];
        $statusColors = [
            'completed' => 'success',
            'in_progress' => 'warning',
            'not_started' => 'gray',
        ];
    @endphp

    <x-filament::section :heading="__('أدوات التحليل')" icon="heroicon-o-adjustments-horizontal">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المادة') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="subjectId">
                        <option value="">{{ __('كل المواد') }}</option>
                        @foreach ($subjectOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="classroomId">
                        <option value="">{{ __('كل الأقسام') }}</option>
                        @foreach ($classroomOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2 xl:col-span-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('بحث') }}</span>
                <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('ابحث عن مادة أو قسم...') }}"
                    />
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('الحالة') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="progressStatus">
                        @foreach ($statusFilterOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('ترتيب حسب') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="sortBy">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('الصفوف في الصفحة') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="perPage">
                        @foreach ($perPageOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
            @if (filled($this->subjectId) || filled($this->classroomId))
                <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="clearFilters">
                    {{ __('العودة إلى الملخص') }}
                </x-filament::button>
            @endif

            <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="resetTableTools">
                {{ __('إعادة تعيين الأدوات') }}
            </x-filament::button>

            <x-filament::button color="primary" icon="heroicon-o-arrow-down-tray" wire:click="exportCsv">
                {{ __('تصدير CSV') }}
            </x-filament::button>
        </div>
    </x-filament::section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            [
                'label' => __('عدد الأزواج'),
                'value' => $stats['pairs'],
                'hint' => null,
                'icon' => 'heroicon-o-rectangle-stack',
                'color' => 'text-primary-600 dark:text-primary-400',
            ],
            [
                'label' => __('متوسط التقدم'),
                'value' => $stats['average'].'%',
                'hint' => $stats['completed_pairs'].' / '.$stats['pairs'].' '.__('مكتمل'),
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'text-primary-600 dark:text-primary-400',
            ],
            [
                'label' => __('الموارد المنجزة'),
                'value' => $stats['completed_resources'].' / '.$stats['total_resources'],
                'hint' => null,
                'icon' => 'heroicon-o-check-badge',
                'color' => 'text-success-600 dark:text-success-400',
            ],
            [
                'label' => __('أزواج في خطر'),
                'value' => $stats['at_risk'],
                'hint' => null,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => $stats['at_risk'] > 0
                    ? 'text-danger-600 dark:text-danger-400'
                    : 'text-gray-400 dark:text-gray-500',
            ],
        ] as $card)
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <x-filament::icon :icon="$card['icon']" @class(['h-5 w-5 shrink-0', $card['color']]) />
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                @if ($card['hint'])
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $card['hint'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    @if ($detail)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => __('نسبة التقدم'), 'value' => $detail['percentage'].'%'],
                ['label' => __('الموارد المنجزة'), 'value' => $detail['completed_resources'].' / '.$detail['total_resources']],
                ['label' => __('المقاطع المكتملة'), 'value' => $detail['completed_sequences'].' / '.$detail['total_sequences']],
                ['label' => __('القسم'), 'value' => $detail['classroom']->display_name()],
            ] as $card)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $detail['subject']->name }} — {{ $detail['classroom']->display_name() }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $detail['level']?->display_name() }}
                    </p>
                </div>
                <span class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $detail['percentage'] }}%</span>
            </div>

            <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                <div class="h-3 rounded-full bg-primary-500" style="width: {{ $detail['percentage'] }}%"></div>
            </div>
        </div>

        <x-filament::section :heading="__('تفصيل الميادين والمقاطع')" icon="heroicon-o-list-bullet">
            @forelse ($detail['domains'] as $domainRow)
                <div class="space-y-4 border-b border-gray-100 py-5 first:pt-0 last:border-0 last:pb-0 dark:border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $domainRow['domain']->name }}
                            </h3>
                            <x-filament::badge :color="$statusColors[$domainRow['status']]" size="sm">
                                {{ $statusLabels[$domainRow['status']] }}
                            </x-filament::badge>
                        </div>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            {{ $domainRow['completed_resources'] }} / {{ $domainRow['total_resources'] }}
                            ({{ $domainRow['percentage'] }}%)
                        </span>
                    </div>

                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                        <div class="h-2 rounded-full bg-primary-500" style="width: {{ $domainRow['percentage'] }}%"></div>
                    </div>

                    <div class="grid gap-3">
                        @foreach ($domainRow['sequences'] as $sequenceRow)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-950 dark:text-white">
                                            {{ $sequenceRow['sequence']->name }}
                                        </h4>
                                        <x-filament::badge :color="$statusColors[$sequenceRow['status']]" size="sm">
                                            {{ $statusLabels[$sequenceRow['status']] }}
                                        </x-filament::badge>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sequenceRow['completed_resources'] }} / {{ $sequenceRow['total_resources'] }}
                                    </span>
                                </div>

                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                    <div class="h-1.5 rounded-full bg-primary-500" style="width: {{ $sequenceRow['percentage'] }}%"></div>
                                </div>

                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ($sequenceRow['resources'] as $resourceRow)
                                        <div @class([
                                            'flex items-center gap-2 rounded-lg px-3 py-2 text-sm',
                                            'bg-success-50 text-success-800 dark:bg-success-500/20 dark:text-success-400' => $resourceRow['completed'],
                                            'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400' => ! $resourceRow['completed'],
                                        ])>
                                            <x-filament::icon
                                                :icon="$resourceRow['completed'] ? 'heroicon-o-check-circle' : 'heroicon-o-clock'"
                                                class="h-5 w-5 shrink-0"
                                            />
                                            <span>{{ $resourceRow['resource']->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-filament-tables::empty-state
                    :heading="__('لا يوجد منهاج معرف لهذه المادة والمستوى.')"
                    :description="__('يمكن لمدير النظام إضافة الميادين والمقاطع والموارد من شاشات المنهاج.')"
                    icon="heroicon-o-map"
                />
            @endforelse
        </x-filament::section>
    @endif

    <x-filament::section
        :heading="__('ملخص الأقسام')"
        :description="__('تُحسب النسبة من الموارد المعرفية التي أكملتها الحصص المنجزة.')"
        icon="heroicon-o-table-cells"
    >
        @if ($overview->isEmpty())
            <x-filament-tables::empty-state
                :heading="__('لا توجد حصص مسندة بعد.')"
                :description="__('ستظهر الأقسام هنا بعد إسناد الحصص إلى المعلمين.')"
                icon="heroicon-o-calendar-days"
            />
        @elseif ($scopedOverview->isEmpty())
            <x-filament-tables::empty-state
                :heading="__('لا توجد نتائج مطابقة')"
                :description="__('لا يوجد زوج مادة وقسم يطابق عوامل التصفية المختارة.')"
                icon="heroicon-o-funnel"
            />

            <div class="mt-4 text-center">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="clearFilters">
                    {{ __('إعادة تعيين عوامل التصفية') }}
                </x-filament::button>
            </div>
        @elseif ($filteredOverview->isEmpty())
            <x-filament-tables::empty-state
                :heading="__('لا توجد نتائج مطابقة')"
                :description="__('جرّب تعديل البحث أو عوامل التصفية لعرض أزواج أخرى.')"
                icon="heroicon-o-magnifying-glass"
            />

            <div class="mt-4 text-center">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="resetTableTools">
                    {{ __('إعادة تعيين الأدوات') }}
                </x-filament::button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start dark:border-white/10">
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('المادة') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('القسم') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('الحالة') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('المقاطع المكتملة') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('الموارد المنجزة') }}</th>
                            <th class="min-w-52 px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('نسبة التقدم') }}</th>
                            <th class="px-3 py-3 text-end font-semibold text-gray-950 dark:text-white">{{ __('عرض') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($paginator as $row)
                            @php
                                $status = $this->rowStatus($row);
                                $atRisk = $this->isAtRisk($row);
                                $isSelected = $detail
                                    && $detail['classroom']->id === $row['classroom']->id
                                    && $detail['subject']->id === $row['subject']->id;
                            @endphp
                            <tr
                                wire:key="curriculum-progress-{{ $row['classroom']->id }}-{{ $row['subject']->id }}"
                                wire:click="showDetail({{ $row['subject']->id }}, {{ $row['classroom']->id }})"
                                @class([
                                    'cursor-pointer transition',
                                    'bg-amber-50/90 dark:bg-amber-500/15' => $isSelected,
                                    'hover:bg-amber-50/50 dark:hover:bg-amber-500/5' => ! $isSelected,
                                ])
                            >
                                <td class="px-3 py-3">
                                    <p class="font-medium {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-900 dark:text-gray-100' }}">{{ $row['subject']->name }}</p>
                                    <p class="mt-0.5 text-xs {{ $isSelected ? 'text-amber-800 dark:text-amber-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $row['level']?->display_name() }}</p>
                                </td>
                                <td class="px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">{{ $row['classroom']->display_name() }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <x-filament::badge :color="$this->rowStatusColor($row)" size="sm">
                                            {{ $this->rowStatusLabel($row) }}
                                        </x-filament::badge>

                                        @if ($atRisk)
                                            <x-filament::badge color="danger" size="sm">
                                                {{ __('في خطر') }}
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $row['completed_sequences'] }} / {{ $row['total_sequences'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $row['completed_resources'] }} / {{ $row['total_resources'] }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 min-w-24 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                            <div
                                                @class([
                                                    'h-2 rounded-full',
                                                    'bg-success-500' => $row['percentage'] >= 100,
                                                    'bg-danger-500' => $row['percentage'] < 50,
                                                    'bg-primary-500' => $row['percentage'] >= 50 && $row['percentage'] < 100,
                                                ])
                                                style="width: {{ $row['percentage'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-11 shrink-0 text-end text-xs font-semibold {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-700 dark:text-gray-200' }}">
                                            {{ $row['percentage'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <x-filament::button
                                        color="gray"
                                        size="xs"
                                        icon="heroicon-o-eye"
                                        wire:click.stop="showDetail({{ $row['subject']->id }}, {{ $row['classroom']->id }})"
                                    >
                                        {{ __('تفاصيل') }}
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('المعروض: :first–:last من :total', [
                        'first' => $paginator->firstItem() ?? 0,
                        'last' => $paginator->lastItem() ?? 0,
                        'total' => $paginator->total(),
                    ]) }}
                </p>

                @if ($paginator->hasPages())
                    <x-filament::pagination :paginator="$paginator" :page-options="[]" />
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
