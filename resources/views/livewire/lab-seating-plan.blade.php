{{--
    U-Shape Lab Seating Plan.
    Arrange working lab stations in a U-shape and assign students to them.
--}}
@php
    $labs = $labs ?? collect();
    $groups = $groups ?? collect();

    $selectedLab = $labs->firstWhere('id', $this->labId);
    $selectedGroup = $groups->firstWhere('id', $this->groupId);

    $workingStations = $this->stations;
    $assignedCount = $this->assignedCount;
    $totalStations = $this->availableStationsCount;

    // Split stations into U-shape groups: top row, left column, right column
    $stationCount = count($workingStations);
    $topCount = (int) ceil($stationCount / 3);
    $leftCount = (int) floor(($stationCount - $topCount) / 2);
    $rightCount = $stationCount - $topCount - $leftCount;

    $topStations = array_slice($workingStations, 0, $topCount);
    $leftStations = array_slice($workingStations, $topCount, $leftCount);
    $rightStations = array_slice($workingStations, $topCount + $leftCount, $rightCount);
@endphp

<div class="space-y-6">
    {{-- Header ---------------------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ __('مخطط جلوس المخبر') }}</h2>
                    @if ($this->existingPlanId)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            {{ __('خطة محفوظة مسبقاً') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('توزيع تلاميذ الفوج على محطات المختبر بشكل حرف U وحفظ خطة الجلوس') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($selectedLab && $selectedGroup)
                    <x-filament::button
                        wire:click="autoAssign"
                        color="success"
                        icon="heroicon-o-sparkles"
                        :disabled="count($this->students) === 0"
                    >
                        {{ __('توزيع تلقائي') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="clearAssignments"
                        color="gray"
                        icon="heroicon-o-trash"
                        :disabled="$assignedCount === 0"
                    >
                        {{ __('تفريغ') }}
                    </x-filament::button>
                @endif

                <x-filament::button
                    wire:click="savePlan"
                    :disabled="$assignedCount === 0"
                    icon="heroicon-o-check-circle"
                >
                    {{ __('حفظ خطة الجلوس') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="resetStations"
                    color="gray"
                    icon="heroicon-o-arrow-path"
                >
                    {{ __('إعادة تعيين') }}
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- Controls --------------------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:items-end">
            {{-- Lab Select --}}
            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المختبر') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="labId"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none"
                    >
                        <option value="" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ __('اختر المختبر') }}</option>
                        @foreach ($labs as $lab)
                            <option value="{{ $lab->id }}" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ $lab->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            {{-- Group Select --}}
            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('الفوج') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model.live="groupId"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none"
                    >
                        <option value="" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ __('اختر الفوج') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ $group->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            {{-- Plan Name --}}
            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('اسم الخطة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="planName"
                        placeholder="{{ __('مثال: خطة الجلوس - صباح الأحد') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400"
                    >
                </x-filament::input.wrapper>
            </div>

            {{-- Notes --}}
            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('ملاحظات') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <textarea
                        wire:model.live.debounce.300ms="planNotes"
                        rows="1"
                        placeholder="{{ __('ملاحظات اختيارية حول خطة الجلوس') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400"
                    ></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Counters Grid --}}
        <div class="mt-4 grid gap-3 grid-cols-2 md:grid-cols-4">
            <div class="rounded-lg border border-stone-200 p-3 text-center dark:border-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('إجمالي المحطات') }}</p>
                <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $totalStations }}</p>
            </div>
            <div class="rounded-lg border border-emerald-200 p-3 text-center dark:border-emerald-500/30 bg-emerald-50/70 dark:bg-emerald-900/20">
                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ __('المحطات المشغولة') }}</p>
                <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ $assignedCount }}</p>
            </div>
            <div class="rounded-lg border border-rose-200 p-3 text-center dark:border-rose-500/30 bg-rose-50/70 dark:bg-rose-900/20">
                <p class="text-xs font-medium text-rose-700 dark:text-rose-300">{{ __('المحطات الفارغة') }}</p>
                <p class="text-2xl font-bold text-rose-800 dark:text-rose-200">{{ max(0, $totalStations - $assignedCount) }}</p>
            </div>
            <div class="rounded-lg border border-blue-200 p-3 text-center dark:border-blue-500/30 bg-blue-50/70 dark:bg-blue-900/20">
                <p class="text-xs font-medium text-blue-700 dark:text-blue-300">{{ __('تلاميذ الفوج') }}</p>
                <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ count($this->students) }}</p>
            </div>
        </div>
    </div>

    {{-- U-Shape Seating Layout ------------------------------------------- --}}
    @if ($selectedLab && $selectedGroup)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ __('خطة الجلوس — :lab (:group)', ['lab' => $selectedLab->name, 'group' => $selectedGroup->display_name()]) }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('اضغط على محطة لتحديدها (أصفر)، ثم اضغط على تلميذ لتعيينه بها، أو استخدم "توزيع تلقائي".') }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-medium dark:bg-emerald-900/30 dark:text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                        {{ __('متاحة') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-amber-100 text-amber-700 text-xs font-medium dark:bg-amber-900/30 dark:text-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                        {{ __('محددة للتعيين') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-medium dark:bg-blue-900/30 dark:text-blue-300">
                        <span class="w-2 h-2 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                        {{ __('مشغولة') }}
                    </span>
                </div>
            </div>

            {{-- Top row of U --------------------------------------------------- --}}
            <div class="flex flex-wrap justify-center gap-3 mb-8">
                @foreach ($topStations as $station)
                    @php
                        $isSelected = $this->selectedStationId === $station['id'];
                        $isAssigned = ! empty($station['student_id']);
                        $bgClass = $isSelected
                            ? 'bg-amber-100 dark:bg-amber-900/40 ring-2 ring-amber-500 dark:ring-amber-400 border-amber-300'
                            : ($isAssigned
                                ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700/50'
                                : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700/40');
                        $textClass = $isAssigned
                            ? 'text-blue-700 dark:text-blue-300'
                            : 'text-emerald-700 dark:text-emerald-300';
                    @endphp
                    <div
                        wire:click="selectStation({{ $station['id'] }})"
                        class="w-24 min-h-[90px] relative flex flex-col items-center justify-between p-2 rounded-xl border {{ $bgClass }} cursor-pointer hover:shadow-md transition"
                    >
                        @if ($isAssigned)
                            <button
                                wire:click.stop="unassignStudent({{ $station['id'] }})"
                                title="{{ __('إلغاء التعيين') }}"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow"
                            >
                                &times;
                            </button>
                        @endif

                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-xs">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                        </div>
                        <div class="text-[11px] font-bold {{ $textClass }} text-center">
                            {{ __('محطة') }} {{ $station['station_number'] }}
                        </div>
                        <div class="text-[10px] text-gray-800 dark:text-gray-200 font-medium truncate w-full text-center mt-1">
                            {{ $station['student_name'] ?? __('— فارغة —') }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Left and right columns of U ----------------------------------- --}}
            <div class="flex justify-between items-start gap-4">
                {{-- Left column --}}
                <div class="flex flex-col gap-3">
                    @foreach (array_reverse($leftStations) as $station)
                        @php
                            $isSelected = $this->selectedStationId === $station['id'];
                            $isAssigned = ! empty($station['student_id']);
                            $bgClass = $isSelected
                                ? 'bg-amber-100 dark:bg-amber-900/40 ring-2 ring-amber-500 dark:ring-amber-400 border-amber-300'
                                : ($isAssigned
                                    ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700/50'
                                    : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700/40');
                            $textClass = $isAssigned
                                ? 'text-blue-700 dark:text-blue-300'
                                : 'text-emerald-700 dark:text-emerald-300';
                        @endphp
                        <div
                            wire:click="selectStation({{ $station['id'] }})"
                            class="w-24 min-h-[90px] relative flex flex-col items-center justify-between p-2 rounded-xl border {{ $bgClass }} cursor-pointer hover:shadow-md transition"
                        >
                            @if ($isAssigned)
                                <button
                                    wire:click.stop="unassignStudent({{ $station['id'] }})"
                                    title="{{ __('إلغاء التعيين') }}"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow"
                                >
                                    &times;
                                </button>
                            @endif

                            <div class="flex items-center justify-center w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-xs">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                            </div>
                            <div class="text-[11px] font-bold {{ $textClass }} text-center">
                                {{ __('محطة') }} {{ $station['station_number'] }}
                            </div>
                            <div class="text-[10px] text-gray-800 dark:text-gray-200 font-medium truncate w-full text-center mt-1">
                                {{ $station['student_name'] ?? __('— فارغة —') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Center: Teacher desk and status summary --}}
                <div class="flex-1 flex flex-col items-center justify-center py-10 px-4">
                    <div class="w-40 py-3 px-4 rounded-2xl border-2 border-dashed border-gray-300 dark:border-white/10 flex flex-col items-center justify-center bg-gray-50/80 dark:bg-gray-800/40 text-center">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-7 w-7 text-amber-500 mb-1"/>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ __('منصة الأستاذ \/ السبورة') }}</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">{{ __('مقدمة المخبر') }}</span>
                    </div>

                    @if ($this->selectedStationId)
                        @php
                            $currentSelectedStation = collect($this->stations)->firstWhere('id', $this->selectedStationId);
                        @endphp
                        <div class="mt-6 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/40 text-center animate-pulse">
                            <span class="text-xs font-bold text-amber-800 dark:text-amber-200">
                                👈 {{ __('المحطة :num محددة: انقر على تلميذ من القائمة بالأسفل لتعيينه بها', ['num' => $currentSelectedStation['station_number'] ?? '']) }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Right column --}}
                <div class="flex flex-col gap-3">
                    @foreach ($rightStations as $station)
                        @php
                            $isSelected = $this->selectedStationId === $station['id'];
                            $isAssigned = ! empty($station['student_id']);
                            $bgClass = $isSelected
                                ? 'bg-amber-100 dark:bg-amber-900/40 ring-2 ring-amber-500 dark:ring-amber-400 border-amber-300'
                                : ($isAssigned
                                    ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700/50'
                                    : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700/40');
                            $textClass = $isAssigned
                                ? 'text-blue-700 dark:text-blue-300'
                                : 'text-emerald-700 dark:text-emerald-300';
                        @endphp
                        <div
                            wire:click="selectStation({{ $station['id'] }})"
                            class="w-24 min-h-[90px] relative flex flex-col items-center justify-between p-2 rounded-xl border {{ $bgClass }} cursor-pointer hover:shadow-md transition"
                        >
                            @if ($isAssigned)
                                <button
                                    wire:click.stop="unassignStudent({{ $station['id'] }})"
                                    title="{{ __('إلغاء التعيين') }}"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs hover:bg-rose-600 shadow"
                                >
                                    &times;
                                </button>
                            @endif

                            <div class="flex items-center justify-center w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-xs">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                            </div>
                            <div class="text-[11px] font-bold {{ $textClass }} text-center">
                                {{ __('محطة') }} {{ $station['station_number'] }}
                            </div>
                            <div class="text-[10px] text-gray-800 dark:text-gray-200 font-medium truncate w-full text-center mt-1">
                                {{ $station['student_name'] ?? __('— فارغة —') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Students list ------------------------------------------------------- --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ __('قائمة تلاميذ الفوج') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('انقر على تلميذ لتعيينه بالمحطة المحددة حالياً، أو استبدال تعيينه.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-stone-100 text-stone-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ __('المعينون:') }} {{ $assignedCount }} / {{ count($this->students) }}
                    </span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->students as $student)
                    @php
                        $assignedStation = collect($this->stations)->firstWhere('student_id', $student['id']);
                        $isAssigned = ! empty($assignedStation);
                    @endphp
                    <div
                        wire:click="assignStudent({{ $student['id'] }})"
                        class="p-3 rounded-xl border cursor-pointer hover:shadow-sm transition {{ $isAssigned ? 'bg-blue-50/80 dark:bg-blue-900/20 border-blue-200 dark:border-blue-600/40' : 'bg-white dark:bg-gray-800/80 border-gray-200 dark:border-white/10 hover:border-primary-400' }}"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-sm text-gray-950 dark:text-white truncate">{{ $student['full_name'] }}</span>
                            <span class="text-[10px] font-mono text-gray-400 dark:text-gray-500">{{ $student['registration_number'] }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            @if ($isAssigned)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5 text-blue-500"/>
                                    {{ __('محطة') }} {{ $assignedStation['station_number'] }}
                                </span>
                                <button
                                    type="button"
                                    wire:click.stop="unassignStudent({{ $assignedStation['id'] }})"
                                    class="text-[10px] text-rose-500 hover:text-rose-700 font-medium"
                                >
                                    {{ __('إلغاء') }}
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-minus-circle" class="h-3.5 w-3.5"/>
                                    {{ __('غير معيّن') }}
                                </span>
                                <span class="text-[10px] font-medium text-primary-600 dark:text-primary-400">
                                    {{ __('تعيين') }} &larr;
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if (count($this->students) === 0)
                <div class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                    {{ __('لا يوجد تلاميذ مسجلون في هذا الفوج حالياً.') }}
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-white/10 p-8 shadow-xs">
            <x-filament::icon icon="heroicon-o-device-tablet" class="h-10 w-10 text-primary-500 mx-auto mb-3 opacity-80"/>
            <h4 class="text-base font-bold text-gray-950 dark:text-white mb-1">{{ __('حدد المختبر والفوج للمتابعة') }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                {{ __('يرجى اختيار مختبر وفوج من القوائم أعلاه لعرض مخطط الجلوس وتوزيع التلاميذ على المحطات.') }}
            </p>
        </div>
    @endif
</div>
