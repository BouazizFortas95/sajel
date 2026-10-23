<x-filament-panels::page>
    @php
        $overview = $this->overview;
        $detail = $this->detail;
        $subjectOptions = $this->subjectOptions;
        $classroomOptions = $this->classroomOptions;
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

        $selectClasses = 'w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none';

        $optionClasses = 'bg-white text-gray-950 dark:bg-gray-800 dark:text-white';
    @endphp

    <x-filament::section :heading="__('تصفية النتائج')" icon="heroicon-o-funnel">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المادة') }}</span>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="subjectId"
                        class="{{ $selectClasses }}"
                    >
                        <option value="" class="{{ $optionClasses }}">{{ __('كل المواد') }}</option>
                        @foreach ($subjectOptions as $id => $label)
                            <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="classroomId"
                        class="{{ $selectClasses }}"
                    >
                        <option value="" class="{{ $optionClasses }}">{{ __('كل الأقسام') }}</option>
                        @foreach ($classroomOptions as $id => $label)
                            <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </label>

            <div class="flex flex-wrap gap-2 xl:col-span-2 xl:justify-end">
                @if ($detail)
                    <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="clearFilters">
                        {{ __('العودة إلى الملخص') }}
                    </x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

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
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start dark:border-white/10">
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('المادة') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('القسم') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('المقاطع المكتملة') }}</th>
                            <th class="px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('الموارد المنجزة') }}</th>
                            <th class="min-w-40 px-3 py-3 text-start font-semibold text-gray-950 dark:text-white">{{ __('نسبة التقدم') }}</th>
                            <th class="px-3 py-3 text-end font-semibold text-gray-950 dark:text-white">{{ __('عرض') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($overview as $row)
                            @php
                                $isSelected = $detail
                                    && $detail['classroom']->id === $row['classroom']->id
                                    && $detail['subject']->id === $row['subject']->id;
                            @endphp
                            <tr
                                wire:click="showDetail({{ $row['subject']->id }}, {{ $row['classroom']->id }})"
                                @class([
                                    'transition',
                                    'cursor-pointer hover:bg-amber-50/50 dark:hover:bg-amber-500/5',
                                    'bg-amber-50/90 dark:bg-amber-500/15 ring-1 ring-inset ring-amber-500/40 dark:ring-amber-400/40' => $isSelected,
                                ])
                            >
                                <td class="px-3 py-3 font-medium {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-900 dark:text-gray-100' }}">{{ $row['subject']->name }}</td>
                                <td class="px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">{{ $row['classroom']->display_name() }}</td>
                                <td class="whitespace-nowrap px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $row['completed_sequences'] }} / {{ $row['total_sequences'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $row['completed_resources'] }} / {{ $row['total_resources'] }}
                                </td>
                                <td class="px-3 py-3 {{ $isSelected ? 'text-amber-950 dark:text-amber-100 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $row['percentage'] }}%
                                </td>
                                <td class="px-3 py-3 text-end"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
