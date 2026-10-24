<x-filament-panels::page>
    <main wire:poll.30s class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="space-y-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <span>{{ __('التاريخ') }}</span>
                    <input
                        type="date"
                        wire:model.live="selectedDate"
                        class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                </label>

                <label class="space-y-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <span>{{ __('المستوى') }}</span>
                    <select
                        wire:model.live="levelId"
                        class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">{{ __('كل المستويات') }}</option>
                        @foreach ($this->levelOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <span>{{ __('القسم') }}</span>
                    <select
                        wire:model.live="selectedClassroomId"
                        class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">{{ __('كل الأقسام') }}</option>
                        @foreach ($this->classroomOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end gap-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-calendar-days"
                        wire:click="resetFiltersToToday"
                    >
                        {{ __('اليوم') }}
                    </x-filament::button>
                    <p class="flex-1 rounded-lg bg-primary-50 px-3 py-2 text-center text-sm font-bold text-primary-800 dark:bg-primary-950 dark:text-primary-200">
                        {{ __('يتجدد العرض تلقائياً كل 30 ثانية') }}
                    </p>
                </div>
            </div>
        </section>

        @php($data = $this->dashboardData)
        @php($metrics = $data['metrics'])
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/50">
                <p class="text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ __('نسبة توثيق الدفاتر اليومية') }}</p>
                <p class="mt-2 text-3xl font-black text-emerald-950 dark:text-emerald-100">{{ $metrics['daily_compliance_percentage'] }}%</p>
                <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">
                    {{ __('الحصص المنجزة') }}: {{ $metrics['completed_sessions_count'] }} / {{ $metrics['scheduled_sessions_count'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/50">
                <p class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('حصص متأخرة غير موثقة') }}</p>
                <p class="mt-2 text-3xl font-black text-amber-950 dark:text-amber-100">{{ $metrics['overdue_sessions_count'] }}</p>
            </article>

            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900 dark:bg-rose-950/50">
                <p class="text-sm font-bold text-rose-800 dark:text-rose-200">{{ __('الغيابات والتأخرات المسجلة') }}</p>
                <p class="mt-2 text-3xl font-black text-rose-950 dark:text-rose-100">{{ $metrics['attendance_alerts_count'] }}</p>
                <p class="mt-1 text-xs font-bold text-rose-800 dark:text-rose-300">
                    {{ __('التلاميذ كثيرو الغياب') }}: {{ count($data['repeatAbsentees']) }}
                </p>
            </article>

            <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5 dark:border-violet-900 dark:bg-violet-950/50">
                <p class="text-sm font-bold text-violet-800 dark:text-violet-200">{{ __('الحالات الانضباطية قيد المتابعة') }}</p>
                <p class="mt-2 text-3xl font-black text-violet-950 dark:text-violet-100">{{ $metrics['open_behavior_count'] }}</p>
            </article>
        </section>

        <section class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 text-sm dark:border-gray-700 dark:bg-gray-900 sm:grid-cols-4">
            <p class="font-bold text-gray-800 dark:text-gray-100">{{ __('ملخص الأسبوع الحالي') }}</p>
            <p class="text-gray-600 dark:text-gray-300">{{ __('الحصص المستحقة') }}: <strong>{{ $data['week']['due'] }}</strong></p>
            <p class="text-emerald-700 dark:text-emerald-300">{{ __('الحصص المنجزة') }}: <strong>{{ $data['week']['completed'] }}</strong></p>
            <p class="text-amber-700 dark:text-amber-300">{{ __('نسبة التوثيق الأسبوعية') }}: <strong>{{ $data['week']['compliance'] }}%</strong></p>
        </section>

        <section
            x-data="{ activeTab: 'sessions' }"
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex flex-wrap gap-2 border-b border-gray-200 p-3 dark:border-gray-700" role="tablist">
                <button type="button" role="tab" @click="activeTab = 'sessions'" :aria-selected="activeTab === 'sessions'" class="rounded-lg px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 aria-selected:bg-primary-100 aria-selected:text-primary-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:aria-selected:bg-primary-950 dark:aria-selected:text-primary-200">
                    {{ __('متابعة دفاتر النصوص اليومية') }}
                </button>
                <button type="button" role="tab" @click="activeTab = 'attendance'" :aria-selected="activeTab === 'attendance'" class="rounded-lg px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 aria-selected:bg-primary-100 aria-selected:text-primary-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:aria-selected:bg-primary-950 dark:aria-selected:text-primary-200">
                    {{ __('مراقبة الغيابات والتأخرات') }}
                </button>
                <button type="button" role="tab" @click="activeTab = 'behavior'" :aria-selected="activeTab === 'behavior'" class="rounded-lg px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 aria-selected:bg-primary-100 aria-selected:text-primary-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:aria-selected:bg-primary-950 dark:aria-selected:text-primary-200">
                    {{ __('السجل الانضباطي والسلوك') }}
                </button>
            </div>

            <div x-cloak x-show="activeTab === 'sessions'" role="tabpanel" class="overflow-x-auto p-4">
                @if (count($data['overdueSessions']))
                    <table class="w-full min-w-[760px] text-start text-sm">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2">{{ __('الأستاذ(ة)') }}</th>
                                <th class="px-3 py-2">{{ __('المادة') }}</th>
                                <th class="px-3 py-2">{{ __('القسم') }}</th>
                                <th class="px-3 py-2">{{ __('الفترة الزمنية') }}</th>
                                <th class="px-3 py-2">{{ __('الحالة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($data['overdueSessions'] as $session)
                                @php($schedule = $session['schedule'])
                                @php($classroom = $schedule->group?->classroom ?? $schedule->classroom)
                                <tr class="text-gray-800 dark:text-gray-100">
                                    <td class="px-3 py-3 font-semibold">{{ $schedule->user?->name ?? '—' }}</td>
                                    <td class="px-3 py-3">{{ $schedule->subject?->name ?? '—' }}</td>
                                    <td class="px-3 py-3">{{ $classroom?->name ?? '—' }}{{ $schedule->group ? ' — '.$schedule->group->name : '' }}</td>
                                    <td class="px-3 py-3">{{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}</td>
                                    <td class="px-3 py-3">
                                        @php($status = $session['status'])
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $status === \App\Enums\DailyLogStatus::Completed->value,
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $status === \App\Enums\DailyLogStatus::Planned->value,
                                            'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200' => $status === 'missing',
                                            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $status === \App\Enums\DailyLogStatus::Cancelled->value,
                                        ])>
                                            {{ $status === 'missing' ? __('غير مسجل') : \App\Enums\DailyLogStatus::from($status)->label() }}
                                        </span>
                                        @if ($session['overdue'])
                                            <span class="ms-1 text-xs font-bold text-rose-700 dark:text-rose-300">{{ __('متأخرة') }}</span>
                                        @endif
                                        @if ($session['log'])
                                            <a
                                                class="ms-2 inline-flex text-xs font-semibold text-primary-700 underline dark:text-primary-300"
                                                href="{{ \App\Filament\Resources\DailyLogResource::getUrl('edit', ['record' => $session['log']]) }}"
                                            >
                                                {{ __('عرض الدفتر') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد حصص متأخرة غير موثقة لهذا التاريخ') }}</p>
                @endif
            </div>

            <div x-cloak x-show="activeTab === 'attendance'" role="tabpanel" class="grid gap-6 p-4 lg:grid-cols-2">
                <div>
                    <h3 class="mb-3 font-bold text-gray-900 dark:text-white">{{ __('الغيابات والتأخرات المسجلة') }}</h3>
                    <div class="space-y-2">
                        @forelse ($data['absences'] as $attendance)
                            @php($student = $attendance->student)
                            <article class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $student?->full_name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $student?->group?->classroom?->name ?? '—' }} — {{ $student?->group?->name ?? '—' }}
                                        · {{ $attendance->dailyLog?->schedule?->subject?->name ?? '—' }}
                                        · {{ $attendance->dailyLog?->schedule?->user?->name ?? '—' }}
                                    </p>
                                    @if ($attendance->remarks)
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $attendance->remarks }}</p>
                                    @endif
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200' => $attendance->status?->value === 'absent',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $attendance->status?->value === 'late',
                                ])>{{ $attendance->status?->label() }}</span>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد غيابات أو تأخرات مسجلة') }}</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3 class="mb-3 font-bold text-gray-900 dark:text-white">{{ __('التلاميذ كثيرو الغياب') }}</h3>
                    <div class="space-y-2">
                        @forelse ($data['repeatAbsentees'] as $repeat)
                            @php($student = $repeat['student'])
                            <article class="flex items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/40">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $student->full_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $student->group?->classroom?->name ?? '—' }} — {{ $student->group?->name ?? '—' }}</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900 dark:bg-amber-900 dark:text-amber-100">
                                    {{ __('غياب :count مرات', ['count' => $repeat['absence_count']]) }}
                                </span>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد حالات غياب متكرر خلال آخر 30 يوماً') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-cloak x-show="activeTab === 'behavior'" role="tabpanel" class="space-y-3 p-4">
                @forelse ($data['observations'] as $observation)
                    <article class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $observation->student?->full_name ?? '—' }}</p>
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-bold text-violet-800 dark:bg-violet-950 dark:text-violet-200">
                                    {{ __(match ($observation->category) {
                                        'negative' => 'سلبي',
                                        'disciplinary' => 'انضباطي',
                                        default => 'ملاحظة',
                                    }) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $observation->date?->format('Y-m-d') }}</span>
                            </div>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $observation->notes }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $observation->student?->group?->classroom?->name ?? '—' }} — {{ $observation->student?->group?->name ?? '—' }}
                                · {{ $observation->recorder?->name ?? $observation->observed_by ?? '—' }}
                                @if ($observation->schedule)
                                    · {{ $observation->schedule->subject?->name }} ({{ $observation->schedule->start_time?->format('H:i') }} - {{ $observation->schedule->end_time?->format('H:i') }})
                                @endif
                            </p>
                            <div x-data class="mt-3 flex flex-wrap items-center gap-2">
                                <textarea
                                    x-ref="followUpNotes"
                                    rows="1"
                                    maxlength="2000"
                                    class="min-w-[16rem] flex-1 rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    placeholder="{{ __('ملاحظات المتابعة (اختياري)') }}"
                                    aria-label="{{ __('ملاحظات المتابعة (اختياري)') }}"
                                ></textarea>
                                <x-filament::button
                                    size="sm"
                                    color="success"
                                    icon="heroicon-o-check"
                                    x-on:click="$wire.resolveBehaviorObservation({{ $observation->id }}, $refs.followUpNotes.value || null)"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('تمت المتابعة') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('لا توجد حالات مفتوحة للمتابعة') }}</p>
                @endforelse
            </div>
        </section>
    </main>
</x-filament-panels::page>
