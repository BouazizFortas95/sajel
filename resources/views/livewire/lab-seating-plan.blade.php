{{--
    U-Shape Lab Seating Plan.
    Arrange working lab stations in a U-shape and assign students to them.
--}}
@php
    use App\Models\LabStation;

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
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ __('مخطط جلوس المخبر') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('توزيع تلاميذ الفوج على محطات المختبر بشكل حرف U وحفظ خطة الجلوس') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-filament::button
                    wire:click="savePlan"
                    disabled="{{ $assignedCount === 0 }}"
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
        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4 xl:items-end">
            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المختبر') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model="labId"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none"
                    >
                        <option value="" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ __('اختر المختبر') }}</option>
                        @foreach ($labs as $lab)
                            <option value="{{ $lab->id }}" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ $lab->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('الفوج') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <select
                        wire:model="groupId"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none"
                    >
                        <option value="" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ __('اختر الفوج') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" class="bg-white text-gray-950 dark:bg-gray-800 dark:text-white">{{ $group->display_name() }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('اسم الخطة') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <input
                        type="text"
                        wire:model="planName"
                        placeholder="{{ __('مثال: خطة الجلوس - صباح الأحد') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400"
                    >
                </x-filament::input.wrapper>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ __('ملاحظات') }}</label>
                <x-filament::input.wrapper class="bg-transparent border-0">
                    <textarea
                        wire:model="planNotes"
                        rows="2"
                        placeholder="{{ __('ملاحظات اختيارية حول خطة الجلوس') }}"
                        class="w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400"
                    ></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="mt-4 grid gap-2 md:grid-cols-3">
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
                <p class="text-2xl font-bold text-rose-800 dark:text-rose-200">{{ $totalStations - $assignedCount }}</p>
            </div>
        </div>
    </div>

    {{-- U-Shape Seating Layout ------------------------------------------- --}}
    @if ($selectedLab && $selectedGroup)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ __('خطة الجلوس — :lab', ['lab' => $selectedLab->name]) }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('المحطات الخضراء متاحة للتعيين، اضغط على محطة ثم تلميذ للتعيين') }}</p>
                </div>

                <div class="flex gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-medium dark:bg-emerald-900/30 dark:text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                        {{ __('متاحة') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-amber-100 text-amber-700 text-xs font-medium dark:bg-amber-900/30 dark:text-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                        {{ __('مختارة') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-medium dark:bg-blue-900/30 dark:text-blue-300">
                        <span class="w-2 h-2 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                        {{ __('مشغولة') }}
                    </span>
                </div>
            </div>

            {{-- Top row of U --------------------------------------------------- --}}
            <div class="flex justify-center gap-2 mb-8">
                @foreach ($topStations as $station)
                    @php
                        $isSelected = $this->selectedStationId === $station['id'];
                        $isAssigned = $station['student_id'];
                        $bgClass = $isSelected
                            ? 'bg-amber-100 dark:bg-amber-900/30 ring-2 ring-amber-500 dark:ring-amber-400'
                            : ($isAssigned
                                ? 'bg-blue-100 dark:bg-blue-900/30'
                                : 'bg-emerald-100 dark:bg-emerald-900/30');
                        $textClass = $isAssigned
                            ? 'text-blue-700 dark:text-blue-300'
                            : 'text-emerald-700 dark:text-emerald-300';
                    @endphp
                    <div
                        wire:click="selectStation({{ $station['id'] }})"
                        class="w-20 flex flex-col items-center gap-1 p-2 rounded-lg border border-transparent {{ $bgClass }} cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    >
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-gray-800 shadow-sm mb-1">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                        </div>
                        <div class="text-[10px] font-bold {{ $textClass }} text-center leading-tight">
                            {{ $station['station_number'] }}
                        </div>
                        @if ($isAssigned)
                            <div class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium truncate w-full text-center">
                                {{ $station['student_name'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Left and right columns of U ----------------------------------- --}}
            <div class="flex justify-between gap-8">
                {{-- Left column --}}
                <div class="flex flex-col gap-2 items-end">
                    @foreach (array_reverse($leftStations) as $station)
                        @php
                            $isSelected = $this->selectedStationId === $station['id'];
                            $isAssigned = $station['student_id'];
                            $bgClass = $isSelected
                                ? 'bg-amber-100 dark:bg-amber-900/30 ring-2 ring-amber-500 dark:ring-amber-400'
                                : ($isAssigned
                                    ? 'bg-blue-100 dark:bg-blue-900/30'
                                    : 'bg-emerald-100 dark:bg-emerald-900/30');
                            $textClass = $isAssigned
                                ? 'text-blue-700 dark:text-blue-300'
                                : 'text-emerald-700 dark:text-emerald-300';
                        @endphp
                        <div
                            wire:click="selectStation({{ $station['id'] }})"
                            class="w-20 flex flex-col items-center gap-1 p-2 rounded-lg border border-transparent {{ $bgClass }} cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-gray-800 shadow-sm mb-1">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                            </div>
                            <div class="text-[10px] font-bold {{ $textClass }} text-center leading-tight">
                                {{ $station['station_number'] }}
                            </div>
                            @if ($isAssigned)
                                <div class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium truncate w-full text-center">
                                    {{ $station['student_name'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Right column --}}
                <div class="flex flex-col gap-2 items-start">
                    @foreach ($rightStations as $station)
                        @php
                            $isSelected = $this->selectedStationId === $station['id'];
                            $isAssigned = $station['student_id'];
                            $bgClass = $isSelected
                                ? 'bg-amber-100 dark:bg-amber-900/30 ring-2 ring-amber-500 dark:ring-amber-400'
                                : ($isAssigned
                                    ? 'bg-blue-100 dark:bg-blue-900/30'
                                    : 'bg-emerald-100 dark:bg-emerald-900/30');
                            $textClass = $isAssigned
                                ? 'text-blue-700 dark:text-blue-300'
                                : 'text-emerald-700 dark:text-emerald-300';
                        @endphp
                        <div
                            wire:click="selectStation({{ $station['id'] }})"
                            class="w-20 flex flex-col items-center gap-1 p-2 rounded-lg border border-transparent {{ $bgClass }} cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-gray-800 shadow-sm mb-1">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 {{ $textClass }}"/>
                            </div>
                            <div class="text-[10px] font-bold {{ $textClass }} text-center leading-tight">
                                {{ $station['station_number'] }}
                            </div>
                            @if ($isAssigned)
                                <div class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-medium truncate w-full text-center">
                                    {{ $station['student_name'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Teacher position (front of room) --}}
            <div class="flex justify-center mt-4">
                <div class="w-24 h-12 rounded-lg border-2 border-dashed border-gray-300 dark:border-white/10 flex items-center justify-center bg-gray-50 dark:bg-gray-800/50">
                    <x-filament::icon icon="heroicon-o-chalkboard-teacher" class="h-5 w-5 text-gray-400 dark:text-gray-500"/>
                    <span class="ml-1 text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ __('المدرس') }}</span>
                </div>
            </div>
        </div>

        {{-- Students list ------------------------------------------------------- --}}
        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ __('التلاميذ غير المعينين') }}</h3>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ count($this->students) }} {{ __('تلميذ') }}
                </span>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->students as $student)
                    @php
                        $isAssigned = collect($this->stations)->contains('student_id', $student['id']);
                    @endphp
                    <div
                        wire:click="assignStudent({{ $student['id'] }})"
                        class="p-3 rounded-lg border border-transparent cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition {{ $isAssigned ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-500/30' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-white/10' }}"
                    >
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $student['full_name'] }}</div>
                            <span class="text-[10px] font-medium {{ $isAssigned ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $student['registration_number'] }}</span>
                        </div>
                        @if ($isAssigned)
                            <div class="mt-1 flex items-center gap-1 text-[10px] text-blue-600 dark:text-blue-400">
                                <x-filament::icon icon="heroicon-o-check" class="h-3 w-3"/>
                                <span>{{ __('معيّن') }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (count($this->students) === 0)
                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                    {{ __('لا يوجد تلاميذ في هذا الفوج. يرجى اختيار فوج آخر.') }}
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-white/5">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-6 w-6 text-gray-400 dark:text-gray-500 mx-auto mb-2"/>
            <p class="text-sm text-gray-500 dark:text-gray-300">{{ __('يرجى اختيار مختبر وفوج لبدء تخطيط الجلوس') }}</p>
        </div>
    @endif
</div>

{{--
    Save confirmation modal --}}
@if ($showSaveModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="cancelSave"></div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-2">{{ __('تأكيد الحفظ') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                {{ __('سيتم حفظ خطة الجلوس :name للمختبر :lab مع :count محطة مشغولة.', [
                    'name' => $this->planName,
                    'lab' => $selectedLab?->name,
                    'count' => $assignedCount,
                ]) }}
            </p>
            <div class="flex justify-end gap-2">
                <x-filament::button
                    wire:click="cancelSave"
                    color="gray"
                >
                    {{ __('إلغاء') }}
                </x-filament::button>
                <x-filament::button
                    wire:click="savePlan"
                >
                    {{ __('تأكيد الحفظ') }}
                </x-filament::button>
            </div>
        </div>
    </div>
@endif
