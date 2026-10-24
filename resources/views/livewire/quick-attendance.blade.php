{{--
    Quick Attendance — Livewire component.
    Allows teachers to mark attendance for the current session's roster
    with one click per student, without navigating through multiple pages.
--}}
@php
    use App\Enums\AttendanceStatus;
    $schedules = $schedules ?? [];
    $students = $students ?? [];
    $statusOptions = $statusOptions ?? [];
    $selectedScheduleId = $selectedScheduleId ?? null;
    $selectedDate = $selectedDate ?? today()->toDateString();
    $attendanceStatuses = $attendanceStatuses ?? [];
    $rosterLoaded = $rosterLoaded ?? false;
    $currentSchedule = $currentSchedule ?? null;
    $rosterSnapshot = $rosterSnapshot ?? [];
    $offlineLabels = [
        'storageError' => __('تعذر حفظ البيانات محلياً. تحقق من مساحة التخزين في المتصفح.'),
        'syncError' => __('تعذرت المزامنة. ستبقى التعديلات محفوظة على هذا الجهاز.'),
        'synced' => __('تمت مزامنة :count تعديلات حضور.'),
    ];

    $statusLabels = [
        AttendanceStatus::Present->value => __('حاضر'),
        AttendanceStatus::Absent->value => __('غائب'),
        AttendanceStatus::Late->value => __('متأخر'),
        AttendanceStatus::Excused->value => __('معفى'),
    ];
    $offlineLabels['statuses'] = $statusLabels;
@endphp

<div
    class="space-y-6"
    x-data="sajelQuickAttendance({
        snapshot: @js($rosterSnapshot),
        schedules: @js($schedules),
        date: @js($selectedDate),
        labels: @js($offlineLabels),
    })"
>
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
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span
                        x-cloak
                        x-show="online"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300"
                    >{{ __('متصل — الحفظ فوري') }}</span>
                    <span
                        x-cloak
                        x-show="!online"
                        class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 dark:bg-amber-900/30 dark:text-amber-200"
                    >{{ __('وضع عدم الاتصال — يتم حفظ الغيابات محلياً') }}</span>
                    <span
                        x-cloak
                        x-show="queuedCount > 0"
                        aria-live="polite"
                        class="text-xs font-medium text-amber-800 dark:text-amber-200"
                        x-text="`{{ __('تعديلات بانتظار المزامنة: :count') }}`.replace(':count', queuedCount)"
                    ></span>
                    <span x-cloak x-show="syncing" class="text-xs text-gray-500">{{ __('جارٍ مزامنة الحضور...') }}</span>
                    <span x-cloak x-show="syncMessage" class="text-xs text-emerald-700 dark:text-emerald-300" x-text="syncMessage"></span>
                    <span x-cloak x-show="syncError" class="text-xs text-rose-700 dark:text-rose-300" x-text="syncMessage"></span>
                    <button
                        x-cloak
                        x-show="queuedCount > 0"
                        type="button"
                        x-on:click="flushQueue()"
                        :disabled="!online || syncing"
                        class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                    >{{ __('مزامنة الآن') }} (<span x-text="queuedCount"></span>)</button>
                </div>
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
                        type="button"
                        x-on:click="selectSchedule({{ $schedule['id'] }})"
                        :aria-pressed="selectedScheduleId === {{ $schedule['id'] }}"
                        class="px-4 py-2.5 rounded-lg border text-sm font-medium transition
                            "
                        :class="selectedScheduleId === {{ $schedule['id'] }}
                            ? 'border-amber-500 bg-amber-50 text-amber-900 dark:border-amber-400 dark:bg-amber-900/20 dark:text-amber-100'
                            : 'border-gray-200 text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
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
    @if ($selectedScheduleId)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white" x-text="snapshot?.schedule_label ?? '{{ __('الحصة المختارة') }}'">
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('نقل الحضور والغياب لكل تلميذ في الحصة.') }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        x-on:click="markAllPresent()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-transparent bg-emerald-500 text-white font-medium text-sm hover:bg-emberald-600 dark:bg-emerald-900/30 dark:hover:bg-emerald-800 dark:text-emerald-50 transition"
                    >
                        <x-filament::icon icon="heroicon-o-check-badge" class="h-4 w-4" />
                        {{ __('تحديد الجميع حاضرين') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="saveAttendance()"
                        :disabled="!online || syncing || queuedCount > 0"
                        class="px-6 py-2.5 rounded-lg bg-amber-500 text-white font-medium text-sm hover:bg-amber-600 dark:bg-amber-900/30 dark:hover:bg-amber-800 transition shadow-sm"
                    >
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                        {{ __('حفظ الحضور') }}
                    </button>
                </div>
            </div>

            {{-- Student Grid --}}
            <div class="sr-only" aria-hidden="true">
                @foreach ($students as $student)
                    <span>{{ $student['full_name'] }}</span>
                @endforeach
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <template x-for="student in roster" :key="student.student_id">
                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-white/5 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-950 dark:text-white" x-text="student.full_name"></span>
                            <span class="text-xs font-bold" :class="statusClass(student.status)" x-text="labels.statuses[student.status]"></span>
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            <span x-text="student.registration_number"></span>
                            <template x-if="student.group !== '—'"><span> • <span x-text="student.group"></span></span></template>
                        </div>

                        <div class="grid gap-2 grid-cols-2">
                            @foreach ($statusOptions as $statusValue => $statusName)
                                <button
                                    type="button"
                                    x-on:click="queueStudent(student, '{{ $statusValue }}')"
                                    class="px-2 py-1.5 rounded border text-xs font-medium transition"
                                    :class="statusButtonClass(student.status, '{{ $statusValue }}')"
                                >
                                    {{ $statusName }}
                                </button>
                            @endforeach
                        </div>
                        <label class="mt-3 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            {{ __('ملاحظات الحضور') }}
                            <textarea
                                x-model="student.remarks"
                                x-on:input="queueStudent(student, student.status, $event.target.value)"
                                rows="2"
                                maxlength="1000"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </label>
                    </div>
                </template>
            </div>

            <div x-cloak x-show="snapshot && roster.length === 0" class="mt-8 text-center text-gray-500 dark:text-gray-400 py-8">
                <x-filament::icon icon="heroicon-o-users" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                <p class="text-lg font-medium text-gray-950 dark:text-white">
                    {{ __('لا يوجد تلاميذ في هذه الحصة.') }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('تأكد من وجود تلاميذ مضافين إلى الفوج أو القسم المحدد لهذه الحصة.') }}
                </p>
            </div>
            <div x-cloak x-show="!snapshot" class="mt-8 text-center text-gray-500 dark:text-gray-400 py-8">
                {{ __('هذه القائمة غير محفوظة على هذا الجهاز. اتصل بالإنترنت لتحميلها أولاً.') }}
            </div>
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
