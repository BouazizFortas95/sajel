@php
    $statuses = [
        [
            'key' => 'present',
            'label' => \App\Enums\AttendanceStatus::Present->label(),
            'value' => $data['status_counts']['present'],
            'icon' => 'heroicon-o-check-circle',
            'tone' => 'success',
        ],
        [
            'key' => 'absent',
            'label' => \App\Enums\AttendanceStatus::Absent->label(),
            'value' => $data['status_counts']['absent'],
            'icon' => 'heroicon-o-x-circle',
            'tone' => 'danger',
        ],
        [
            'key' => 'late',
            'label' => \App\Enums\AttendanceStatus::Late->label(),
            'value' => $data['status_counts']['late'],
            'icon' => 'heroicon-o-clock',
            'tone' => 'warning',
        ],
        [
            'key' => 'excused',
            'label' => \App\Enums\AttendanceStatus::Excused->label(),
            'value' => $data['status_counts']['excused'],
            'icon' => 'heroicon-o-shield-check',
            'tone' => 'info',
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-dashboard.kpi-card
        :label="__('حصص مستحقة')"
        :value="number_format($data['due_sessions'])"
        :description="__('حصص انتهى وقتها في الفترة المحددة، بعد استثناء العطل والحصص الملغاة')"
        icon="heroicon-o-calendar-days"
        tone="gray"
    />

    <x-dashboard.kpi-card
        :label="__('حضور مسجل')"
        :value="number_format($data['recorded_sessions'])"
        :description="__('حصص لها سجل منجز وعلى الأقل صف حضور واحد')"
        icon="heroicon-o-check-badge"
        tone="success"
    />

    <x-dashboard.kpi-card
        :label="__('حضور غير مسجل')"
        :value="number_format($data['not_recorded_sessions'])"
        :description="__('ليست غياباً؛ تعني فقط عدم وجود سجل حضور بعد')"
        icon="heroicon-o-exclamation-circle"
        tone="danger"
    />

    <x-dashboard.kpi-card
        :label="__('صفوف الحضور')"
        :value="number_format($data['attendance_rows'])"
        :description="__('مجموع حالات الحضور المسجلة في الفترة')"
        icon="heroicon-o-clipboard-document-list"
        tone="info"
    />
</div>

<section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-gray-700">
        <div>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('نسبة التوثيق') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('الحصص التي لها سجل حضور') }}</h2>
        </div>

        <span class="text-2xl font-bold text-gray-950 dark:text-white">
            {{ $data['recorded_percentage'] === null ? '—' : $data['recorded_percentage'].'%' }}
        </span>
    </header>

    <div class="space-y-5 p-5">
        <div
            class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
            role="img"
            aria-label="{{ __('نسبة الحصص التي لها سجل حضور') }}: {{ $data['recorded_percentage'] === null ? __('غير متاح') : $data['recorded_percentage'].'%' }}"
        >
            @if ($data['recorded_percentage'] !== null)
                <div class="h-2.5 rounded-full bg-success-500" style="width: {{ $data['recorded_percentage'] }}%"></div>
            @endif
        </div>

        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
            {{ __('القاسم هو الحصص المستحقة في الفترة. لا يُحتسب “غير مسجل” حاضراً ولا غائباً، ولا يُنشئ صفوف حضور وهمية.') }}
        </p>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($statuses as $status)
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-800/80">
                    <div class="flex items-center gap-2">
                        <span @class([
                            'shrink-0',
                            'text-success-600 dark:text-success-400' => $status['tone'] === 'success',
                            'text-danger-600 dark:text-danger-400' => $status['tone'] === 'danger',
                            'text-warning-600 dark:text-warning-400' => $status['tone'] === 'warning',
                            'text-info-600 dark:text-info-400' => $status['tone'] === 'info',
                        ])>
                            <x-filament::icon :icon="$status['icon']" class="h-4 w-4" />
                        </span>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">{{ $status['label'] }}</p>
                    </div>

                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($status['value']) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('المتابعة') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('الغياب المتكرر') }}</h2>
        </header>

        <div class="p-5">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ number_format($data['repeat_absentees']) }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('تلميذاً لديهم 3 أيام غياب مختلفة على الأقل خلال الفترة') }}</p>
                </div>

                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6" />
                </span>
            </div>

            <p class="mt-4 text-xs leading-5 text-gray-500 dark:text-gray-400">
                {{ __('عدد الحالات هو :count صف غياب مسجل. تُحتسب الأيام المختلفة فقط لكل تلميذ.', ['count' => number_format($data['absence_entries'])]) }}
            </p>

            @if ($isAdmin)
                <a
                    href="{{ \App\Filament\Pages\SchoolSupervisionHub::getUrl() }}"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ __('فتح المراقبة العامة') }}
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                </a>
            @else
                <a
                    href="{{ \App\Filament\Pages\QuickAttendance::getUrl() }}"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ __('فتح تسجيل الحضور السريع') }}
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                </a>
            @endif
        </div>
    </section>

    <section class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <header class="border-b border-amber-100 px-5 py-4 dark:border-gray-700">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">{{ __('كيف نقرأ الأرقام؟') }}</p>
            <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ __('تعريفات الحضور') }}</h2>
        </header>

        <div class="space-y-3 p-5 text-sm leading-6 text-gray-600 dark:text-gray-300">
            <p>{{ __('“حاضر” و“غائب” و“متأخر” و“معفى” هي حالات مسجلة فعلياً فقط.') }}</p>
            <p>{{ __('“غير مسجل” لا يعني حاضراً ولا غائباً؛ إنه غياب سجل الحضور نفسه.') }}</p>
            <p>{{ __('المصدر هو سجلات الحضور المرتبطة بسجلات الدروس المنجزة، وليس جدول الحصص وحده.') }}</p>
            <p>{{ __('يُستثنى من الحصص المستحقة كل يوم عطلة مدرسية وكل حصة ملغاة.') }}</p>
        </div>
    </section>
</div>

@if ($data['due_sessions'] === 0 && $data['attendance_rows'] === 0)
    <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-5 py-8 text-center dark:border-amber-700 dark:bg-amber-400/5">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-6 w-6" />
        </span>
        <h3 class="mt-3 font-bold text-gray-900 dark:text-white">{{ __('لا توجد بيانات حضور لهذه الفترة') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('جرّب فترة أوسع أو تحقق من تسجيل الحضور بعد انتهاء الحصص.') }}</p>
    </div>
@endif
