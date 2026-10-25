<x-filament-panels::page>
    @php
        $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
        $attendancePrintUrl = $this->attendancePrintUrl();
        $attendancePdfUrl = $this->attendancePdfUrl();
        $transcriptPrintUrl = $this->transcriptPrintUrl();
        $transcriptPdfUrl = $this->transcriptPdfUrl();

        $selectClasses = 'w-full border border-gray-300 bg-white p-2 text-sm text-gray-950 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 appearance-none';

        $optionClasses = 'bg-white text-gray-950 dark:bg-gray-800 dark:text-white';
    @endphp

    <section class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-black">{{ $schoolSetting->school_name }}</p>
                <p class="text-xs font-semibold">{{ $schoolSetting->directorate_name }}</p>
            </div>
            <div class="text-xs font-semibold">
                <p>{{ __('السنة الدراسية') }}: {{ $schoolSetting->academic_year }}</p>
                @if ($schoolSetting->municipality_name)
                    <p>{{ __('البلدية') }}: {{ $schoolSetting->municipality_name }}</p>
                @endif
                @if ($schoolSetting->school_code)
                    <p>{{ __('الرقم الميكانيكي') }}: <span dir="ltr">{{ $schoolSetting->school_code }}</span></p>
                @endif
            </div>
        </div>
    </section>

    <div class="mb-4">
        <x-filament::tabs :label="__('التقارير الرسمية')">
            <x-filament::tabs.item
                :active="$report === 'timetable'"
                wire:click="setReport('timetable')"
                icon="heroicon-o-calendar-days"
            >
                {{ __('جدول التوقيت الأسبوعي') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$report === 'attendance'"
                wire:click="setReport('attendance')"
                icon="heroicon-o-clipboard-document-check"
            >
                {{ __('تقرير الحضور والغيابات المجمع') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$report === 'transcript'"
                wire:click="setReport('transcript')"
                icon="heroicon-o-academic-cap"
            >
                {{ __('كشف النقاط الرسمي') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$report === 'report-card'"
                wire:click="setReport('report-card')"
                icon="heroicon-o-document-text"
            >
                {{ __('طباعة بطاقة متابعة التلميذ') }}
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    @if ($report === 'timetable')
        <x-filament::section
            :heading="__('جدول التوقيت الأسبوعي')"
            :description="__('اطبع جدول التوقيت الأسبوعي للمعلم أو القسم بصيغة A4.')"
            icon="heroicon-o-calendar-days"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
                @if (auth()->user()?->isAdmin())
                    <label class="grid gap-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المعلم') }}</span>
                        <x-filament::input.wrapper class="bg-transparent border-0">
                            <select
                                wire:model.live="timetableTeacherId"
                                class="{{ $selectClasses }}"
                            >
                                <option value="" class="{{ $optionClasses }}">{{ __('جميع المعلمين') }}</option>
                                @foreach ($this->teacherOptions as $id => $label)
                                    <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                    </label>
                @else
                    <div class="rounded-xl bg-gray-50 border border-gray-200 dark:bg-gray-800/90 dark:border-white/10 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المعلم') }}</p>
                        <p class="mt-1 font-bold text-gray-950 dark:text-white">{{ auth()->user()?->name }}</p>
                    </div>
                @endif

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="timetableClassroomId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل الأقسام') }}</option>
                            @foreach ($this->classroomOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-2 xl:justify-end">
                    <x-filament::button
                        tag="a"
                        href="{{ $this->timetablePrintUrl() }}"
                        target="_blank"
                        rel="noopener"
                        icon="heroicon-o-printer"
                    >
                        {{ __('طباعة') }}
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $this->timetablePdfUrl() }}"
                        target="_blank"
                        rel="noopener"
                        color="gray"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        {{ __('تحميل PDF') }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    @if ($report === 'attendance')
        <x-filament::section
            :heading="__('تقرير الحضور والغيابات المجمع')"
            :description="__('اطبع ملخص الحضور والغياب لكل تلميذ خلال الفترة المحددة.')"
            icon="heroicon-o-clipboard-document-check"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="attendanceClassroomId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل الأقسام') }}</option>
                            @foreach ($this->classroomOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المجموعة') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="attendanceGroupId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل المجموعات') }}</option>
                            @foreach ($this->attendanceGroupOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المادة') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="attendanceSubjectId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل المواد') }}</option>
                            @foreach ($this->attendanceSubjectOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('من تاريخ') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <input
                            type="date"
                            wire:model.live="attendanceFrom"
                            class="{{ $selectClasses }}"
                        >
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('إلى تاريخ') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <input
                            type="date"
                            wire:model.live="attendanceTo"
                            class="{{ $selectClasses }}"
                        >
                    </x-filament::input.wrapper>
                </label>

                <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">
                    @if ($attendancePrintUrl)
                        <x-filament::button
                            tag="a"
                            href="{{ $attendancePrintUrl }}"
                            target="_blank"
                            rel="noopener"
                            icon="heroicon-o-printer"
                        >
                            {{ __('طباعة') }}
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            href="{{ $attendancePdfUrl }}"
                            target="_blank"
                            rel="noopener"
                            color="gray"
                            icon="heroicon-o-arrow-down-tray"
                        >
                            {{ __('تحميل PDF') }}
                        </x-filament::button>
                    @else
                        <x-filament::button disabled icon="heroicon-o-printer">
                            {{ __('اختر القسم أولاً.') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    @if ($report === 'transcript')
        <x-filament::section
            :heading="__('كشف النقاط الرسمي')"
            :description="__('اطبع كشف النقاط الرسمي للمادة والفترة المحددتين.')"
            icon="heroicon-o-academic-cap"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="transcriptClassroomId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل الأقسام') }}</option>
                            @foreach ($this->classroomOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المجموعة') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="transcriptGroupId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل المجموعات') }}</option>
                            @foreach ($this->transcriptGroupOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('المادة') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select
                            wire:model.live="transcriptSubjectId"
                            class="{{ $selectClasses }}"
                        >
                            <option value="" class="{{ $optionClasses }}">{{ __('كل المواد') }}</option>
                            @foreach ($this->transcriptSubjectOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('من تاريخ') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <input
                            type="date"
                            wire:model.live="transcriptFrom"
                            class="{{ $selectClasses }}"
                        >
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('إلى تاريخ') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <input
                            type="date"
                            wire:model.live="transcriptTo"
                            class="{{ $selectClasses }}"
                        >
                    </x-filament::input.wrapper>
                </label>

                <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">
                    @if ($transcriptPrintUrl)
                        <x-filament::button
                            tag="a"
                            href="{{ $transcriptPrintUrl }}"
                            target="_blank"
                            rel="noopener"
                            icon="heroicon-o-printer"
                        >
                            {{ __('طباعة') }}
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            href="{{ $transcriptPdfUrl }}"
                            target="_blank"
                            rel="noopener"
                            color="gray"
                            icon="heroicon-o-arrow-down-tray"
                        >
                            {{ __('تحميل PDF') }}
                        </x-filament::button>
                    @else
                        <x-filament::button disabled icon="heroicon-o-printer">
                            {{ __('اختر المادة أولاً.') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    @if ($report === 'report-card')
        <x-filament::section
            :heading="__('بطاقة المتابعة البيداغوجية والسلوكية للتلميذ')"
            :description="__('اختر تلميذاً من قائمتك المصرح بها لطباعة بطاقة المتابعة للاجتماع مع الولي.')"
            icon="heroicon-o-document-text"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('القسم') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select wire:model.live="reportCardClassroomId" class="{{ $selectClasses }}">
                            <option value="" class="{{ $optionClasses }}">{{ __('كل الأقسام') }}</option>
                            @foreach ($this->reportCardClassroomOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('الفوج') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select wire:model.live="reportCardGroupId" class="{{ $selectClasses }}">
                            <option value="" class="{{ $optionClasses }}">{{ __('كل الأفواج') }}</option>
                            @foreach ($this->reportCardGroupOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ __('التلميذ') }}</span>
                    <x-filament::input.wrapper class="bg-transparent border-0">
                        <select wire:model.live="reportCardStudentId" class="{{ $selectClasses }}">
                            <option value="" class="{{ $optionClasses }}">{{ __('اختر التلميذ') }}</option>
                            @foreach ($this->reportCardStudentOptions as $id => $label)
                                <option value="{{ $id }}" class="{{ $optionClasses }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </label>

                <div class="flex flex-wrap gap-2 md:col-span-2 xl:justify-end">
                    @if ($this->reportCardPrintUrl())
                        <x-filament::button
                            tag="a"
                            href="{{ $this->reportCardPrintUrl() }}"
                            target="_blank"
                            rel="noopener"
                            icon="heroicon-o-printer"
                        >
                            {{ __('بطاقة متابعة التلميذ (طباعة)') }}
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            href="{{ $this->reportCardPdfUrl() }}"
                            color="gray"
                            icon="heroicon-o-arrow-down-tray"
                        >
                            {{ __('تحميل بطاقة المتابعة (PDF)') }}
                        </x-filament::button>
                    @else
                        <x-filament::button disabled icon="heroicon-o-printer">
                            {{ __('اختر التلميذ') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
