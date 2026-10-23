{{--
    Quick Attendance — Livewire component.
    Allows teachers to mark attendance for the current session's roster
    with one click per student, without navigating through multiple pages.
--}}
@php
    use App\Enums\AttendanceStatus;
    use Filament\Support\Colors\Color;

    $schedules = $schedules ?? [];
    $students = $students ?? [];
    $statusOptions = $statusOptions ?? [];
    $selectedScheduleId = $selectedScheduleId ?? null;
    $selectedDate = $selectedDate ?? today()->toDateString();
    $attendanceStatuses = $attendanceStatuses ?? [];
    $rosterLoaded = $rosterLoaded ?? false;
    $currentSchedule = $currentSchedule ?? null;

    $statusColors = [
        AttendanceStatus::Present->value => 'success',
        AttendanceStatus::Absent->value => 'danger',
        AttendanceStatus::Late->value => 'warning',
        AttendanceStatus::Excused->value => 'info',
    ];

    $statusLabels = [
        AttendanceStatus::Present->value => __('حاضر'),
        AttendanceStatus::Absent->value => __('غائب'),
        AttendanceStatus::Late->value => __('متأخر'),
        AttendanceStatus::Excused->value => __('معفى'),
    ];
@endphp

<div class="space-y-6">
    {{-- Header ------------------------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                    {{ __('تسجيل الحضور السريع') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('افتح قائمة تلاميذ الحصة الجارية وسجل الغيابات والتأخرات بنقرة واحدة.') }}
                </p>
            </div>

            <div class="text-right">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('التاريخ') }}
                </p>
                <p class="text-lg font-bold text-gray-950 dark:text-white">
                    {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('l, d/m/Y') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Schedule Selector -------------------------------------------------- --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-3">
                    {{ __('اختر الحصة') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('اختر الحصة التي تريد تسجيل حضور تلاميذها.') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach ($schedules as $schedule)
                    <button
                        wire:click="schedule-selected({{ $schedule['id'] }})"
                        class="px-4 py-2.5 rounded-lg border text-sm font-medium transition
                            @if ($selectedScheduleId === $schedule['id'])
                                border-amber-500 bg-amber-50 text-amber-900 dark:border-amber-400 dark:bg-amber-900/20 dark:text-amber-100
                            @else
                                border-gray-200 text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700
                            @endif
                        "
                    >
                        <div class="font-bold">{{ $schedule['label'] }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $schedule['start'] }} — {{ $schedule['end'] }}
                            @if ($schedule['isCurrent'])
                                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 text-xs font-bold dark:bg-emerald-900/30 dark:text-emerald-400">
                                    {{ __('الآن') }}
                                </span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        @if (empty($schedules))
            <div class="mt-4 text-center text-gray-500 dark:text-gray-400 py-4">
                {{ __('لا توجد حصص مجدولة لك اليوم.') }}
            </div>
        @endif
    </div>

    {{-- Student Roster ----------------------------------------------------- --}}
    @if ($selectedScheduleId && $rosterLoaded)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    @php
                        $schedule = \App\Models\Schedule::query()
                            ->with(['subject', 'classroom.level', 'group'])
                            ->find($selectedScheduleId);
                    @endphp
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ $schedule?->label() ?? __('الحصة المختارة') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('نقل الحضور والغياب لكل تلميذ في الحصة.') }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        wire:click="markAllPresent"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-transparent bg-emerald-500 text-white font-medium text-sm hover:bg-emberald-600 dark:bg-emerald-900/30 dark:hover:bg-emerald-800 dark:text-emerald-50 transition"
                    >
                        <x-filament::icon icon="heroicon-o-check-badge" class="h-4 w-4" />
                        {{ __('تحديد الجميع حاضرين') }}
                    </button>

                    <button
                        wire:click="saveAttendance"
                        class="px-6 py-2.5 rounded-lg bg-amber-500 text-white font-medium text-sm hover:bg-amber-600 dark:bg-amber-900/30 dark:hover:bg-amber-800 transition shadow-sm"
                    >
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                        {{ __('حفظ الحضور') }}
                    </button>
                </div>
            </div>

            {{-- Student Grid --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($students as $student)
                    @php
                        $studentStatus = $attendanceStatuses[$student['id']] ?? AttendanceStatus::Present->value;
                        $statusColor = $statusColors[$studentStatus] ?? 'success';
                        $statusLabel = $statusLabels[$studentStatus] ?? __('حاضر');
                    @endphp
                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-white/5 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-950 dark:text-white">
                                {{ $student['full_name'] }}
                            </span>
                            <span class="text-xs font-bold {{ $statusColor === 'success' ? 'text-emerald-700 dark:text-emerald-400' : ($statusColor === 'danger' ? 'text-rose-700 dark:text-rose-400' : ($statusColor === 'warning' ? 'text-amber-700 dark:text-amber-400' : 'text-sky-700 dark:text-sky-400')) }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            {{ $student['registration_number'] }}
                            @if ($student['group'] !== '—')
                                • {{ $student['group'] }}
                            @endif
                        </div>

                        <div class="grid gap-2 grid-cols-2">
                            @foreach ($statusOptions as $statusValue => $statusName)
                                <button
                                    wire:click="markAttendance({{ $student['id'] }}, '{{ $statusValue }}')"
                                    class="px-2 py-1.5 rounded border text-xs font-medium transition
                                        @if ($studentStatus === $statusValue)
                                            border-{{ $statusColor }}-500 bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700 dark:border-{{ $statusColor }}-400 dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-400
                                        @else
                                            border-transparent bg-transparent text-gray-400 dark:text-gray-500 hover:border-gray-300 dark:hover:border-gray-600
                                        @endif
                                    "
                                >
                                    {{ $statusName }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if (count($students) === 0)
                <div class="mt-8 text-center text-gray-500 dark:text-gray-400 py-8">
                    <x-filament::icon icon="heroicon-o-users" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-lg font-medium text-gray-950 dark:text-white">
                        {{ __('لا يوجد تلاميذ في هذه الحصة.') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('تأكد من وجود تلاميذ مضافين إلى الفوج أو القسم المحدد لهذه الحصة.') }}
                    </p>
                </div>
            @endif
        </div>
    @elseif (!empty($schedules))
        <div class="mt-8 text-center text-gray-500 dark:text-gray-400 py-12">
            <x-filament::icon icon="heroicon-o-calendar-days" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-lg font-medium text-gray-950 dark:text-white">
                {{ __('اختر حصة لتسجيل الحضور') }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('بعد اختيار الحصة، ستظهر قائمة التلاميذ لتسجيل حضورهم وغيابهم وتأخراتهم.') }}
            </p>
        </div>
    @endif
</div>
