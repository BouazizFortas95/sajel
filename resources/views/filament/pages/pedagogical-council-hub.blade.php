<x-filament-panels::page>
    @php
        $data = $this->getViewData();
    @endphp

    <div class="space-y-6">

        {{-- Top Control & Filtering Bar --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg">
                    <x-heroicon-o-academic-cap class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ __('مجالس التعليم والتنسيق البيداغوجي والندوات التربوية') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('الموسم الدراسي:') }} <span class="font-mono font-semibold">{{ $this->academicYear }}</span> · {{ $data['school']?->school_name }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Subject Filter --}}
                <select wire:model.live="selectedSubjectId" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5 px-3">
                    <option value="">{{ __('كافة المواد التعليمية') }}</option>
                    @foreach ($data['subjects'] as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>

                {{-- Action Buttons --}}
                <x-filament::button wire:click="openCouncilModal" size="sm" color="amber" icon="heroicon-m-plus-circle">
                    {{ __('توثيق محضر مجلس') }}
                </x-filament::button>

                <x-filament::button wire:click="openSeminarModal" size="sm" color="gray" icon="heroicon-m-calendar-days">
                    {{ __('برمجة ندوة تربوية') }}
                </x-filament::button>

                <a href="{{ route('print.pedagogical-council.curriculum-pacing.pdf', ['locale' => app()->getLocale(), 'subject' => $this->selectedSubjectId]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                    {{ __('كشف وتيرة المنهاج') }}
                </a>

                <a href="{{ route('print.pedagogical-council.seminar-calendar.pdf', ['locale' => app()->getLocale(), 'subject' => $this->selectedSubjectId]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                    {{ __('رزنامة الندوات') }}
                </a>
            </div>
        </div>

        {{-- 4 Metric Telemetry Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('إجمالي مجالس التعليم') }}</span>
                    <x-heroicon-m-chat-bubble-bottom-center-text class="w-4 h-4 text-blue-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $data['totalCouncils'] }}
                </div>
                <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">
                    {{ $data['completedCouncils'] }} {{ __('مجلس منعقد وموثق') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('نسبة حضور الأساتذة') }}</span>
                    <x-heroicon-m-user-group class="w-4 h-4 text-emerald-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $data['attendanceRate'] }}%
                </div>
                <div class="mt-1 text-[11px] text-gray-500">
                    {{ __('معدل الالتزام البيداغوجي') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('الندوات التربوية') }}</span>
                    <x-heroicon-m-presentation-chart-line class="w-4 h-4 text-purple-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $data['totalSeminars'] }}
                </div>
                <div class="mt-1 text-[11px] text-purple-600 dark:text-purple-400">
                    {{ $data['upcomingSeminars'] }} {{ __('ندوة قادمة مبرمجة') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('جلسات التنسيق الفصلي') }}</span>
                    <x-heroicon-m-arrows-right-left class="w-4 h-4 text-amber-500" />
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
                    {{ $data['coordinationCount'] }}
                </div>
                <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                    {{ __('توحيد الفروض والمشاريع') }}
                </div>
            </div>
        </div>

        {{-- Nav Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('councils')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'councils' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-document-text class="w-4 h-4" />
                {{ __('محاضر مجالس التعليم للمواد') }} ({{ $data['councils']->count() }})
            </button>

            <button wire:click="setTab('pacing')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'pacing' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-chart-bar class="w-4 h-4" />
                {{ __('متابعة وتيرة تنفيذ المنهاج وتدرج التعلمات') }}
            </button>

            <button wire:click="setTab('seminars')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $activeTab === 'seminars' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <x-heroicon-m-calendar-days class="w-4 h-4" />
                {{ __('رزنامة الندوات وأيام التكوين والتفتيش') }} ({{ $data['seminars']->count() }})
            </button>
        </div>

        {{-- Tab 1: Councils --}}
        @if ($activeTab === 'councils')
            <div class="space-y-4">
                @forelse ($data['councils'] as $council)
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                    {{ $council->council_type->label() }}
                                </span>
                                @if ($council->subject)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $council->subject->name }}
                                    </span>
                                @endif
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $council->pv_reference }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                {{ $council->title }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>📅 {{ $council->held_at?->format('d/m/Y - H:i') }}</span>
                                <span>📍 {{ $council->location ?? __('قاعة الأساتذة') }}</span>
                                <span>👤 {{ __('المنسق:') }} <strong>{{ $council->coordinator?->name ?? __('غير محدد') }}</strong></span>
                                <span>👥 {{ __('الحضور:') }} <strong>{{ $council->attendances->where('is_present', true)->count() }}</strong> / {{ $council->attendances->count() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end md:self-center">
                            <a href="{{ route('print.pedagogical-council.pv', ['locale' => app()->getLocale(), 'council' => $council->id]) }}" target="_blank" class="px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg flex items-center gap-1.5">
                                <x-heroicon-m-eye class="w-4 h-4" />
                                {{ __('معاينة') }}
                            </a>
                            <a href="{{ route('print.pedagogical-council.pv.pdf', ['locale' => app()->getLocale(), 'council' => $council->id]) }}" target="_blank" class="px-3 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 rounded-lg flex items-center gap-1.5">
                                <x-heroicon-m-printer class="w-4 h-4" />
                                {{ __('المحضر الرسمي (PDF)') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-gray-500">
                        <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                        <p class="font-semibold">{{ __('لم يتم توثيق أي مجلس تعليم في هذا الموسم بعد.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('اضغط على زر "توثيق محضر مجلس" لإدخال أول محضر.') }}</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- Tab 2: Pacing --}}
        @if ($activeTab === 'pacing')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ __('مؤشرات تدرج التعلمات ونسب إنجاز المقاطع حسب المادة والمستوى') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('مقارنة آلية للحصص المنجزة فعلياً بدفتر النصوص مقابل العتبة المعيارية للرزنامة الوزارية.') }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">{{ __('المادة التعليمية') }}</th>
                                <th class="p-3">{{ __('المستوى التعليمي') }}</th>
                                <th class="p-3 text-center">{{ __('الحصص المخططة') }}</th>
                                <th class="p-3 text-center">{{ __('الحصص المنجزة') }}</th>
                                <th class="p-3 text-center">{{ __('نسبة الإنجاز') }}</th>
                                <th class="p-3 text-center">{{ __('النسبة المعيارية') }}</th>
                                <th class="p-3 text-center">{{ __('الفارق') }}</th>
                                <th class="p-3 text-center">{{ __('التقييم البيداغوجي') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($data['pacingData'] as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 font-mono text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $row['subject']?->name }}</td>
                                    <td class="p-3">{{ $row['level']?->name }}</td>
                                    <td class="p-3 text-center font-mono">{{ $row['totalLessons'] }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $row['completedLessons'] }}</td>
                                    <td class="p-3 text-center font-mono font-bold">{{ $row['completionRate'] }}%</td>
                                    <td class="p-3 text-center font-mono text-gray-500">{{ $row['expectedRate'] }}%</td>
                                    <td class="p-3 text-center font-mono font-bold {{ $row['lag'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : ($row['lag'] >= -15 ? 'text-amber-600' : 'text-rose-600') }}">
                                        {{ $row['lag'] > 0 ? '+'.$row['lag'] : $row['lag'] }}%
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($row['pacingStatus'] === 'on_track')
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                {{ __('على المسار الطبيعي') }}
                                            </span>
                                        @elseif ($row['pacingStatus'] === 'slight_delay')
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                                {{ __('تأخر طفيف') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                {{ __('تأخر مقلق (معالجة)') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-400">
                                        {{ __('لا توجد بيانات حصص كافية لحساب مؤشرات وتيرة المنهاج.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Tab 3: Seminars --}}
        @if ($activeTab === 'seminars')
            <div class="space-y-4">
                @forelse ($data['seminars'] as $sem)
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                                    {{ $sem->seminar_type->label() }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $sem->subject?->name }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $sem->status->value === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $sem->status->label() }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                {{ $sem->title }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>📅 {{ $sem->scheduled_at?->format('d/m/Y') }}</span>
                                <span>🏢 {{ $sem->host_school ?? __('متوسطة الشهيد العربي بن مهيدي') }}</span>
                                <span>👤 {{ __('المفتش:') }} <strong>{{ $sem->inspector_name ?? __('مفتش المقاطعة') }}</strong></span>
                                <span>🎤 {{ __('المنشط:') }} <strong>{{ $sem->animator?->name ?? '—' }}</strong></span>
                                @if ($sem->official_call_number)
                                    <span class="font-mono text-gray-400"># {{ $sem->official_call_number }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-gray-500">
                        <x-heroicon-o-calendar-days class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                        <p class="font-semibold">{{ __('لا توجد ندوات تربوية مبرمجة في الرزنامة الحالية.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('اضغط على "برمجة ندوة تربوية" لإضافة محطة تكوينية جديدة.') }}</p>
                    </div>
                @endforelse
            </div>
        @endif

    </div>

    {{-- Create Council Modal --}}
    @if ($isCouncilModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full p-6 border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        {{ __('توثيق محضر مجلس التعليم والتنسيق البيداغوجي') }}
                    </h3>
                    <button wire:click="closeCouncilModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('المادة التعليمية:') }}</label>
                        <select wire:model="councilSubjectId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="">{{ __('تنسيق عام / متعدد المواد') }}</option>
                            @foreach ($data['subjects'] as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('نوع الجلسة أو المجلس:') }}</label>
                        <select wire:model="councilType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="subject_council">{{ __('مجلس التعليم للمادة') }}</option>
                            <option value="term_coordination">{{ __('جلسة التنسيق البيداغوجي الفصلي') }}</option>
                            <option value="administrative_coordination">{{ __('مجلس التنسيق الإداري والتربوي') }}</option>
                            <option value="internal_seminar">{{ __('ندوة تربوية داخلية بالمؤسسة') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الفصل الدراسي:') }}</label>
                        <select wire:model="councilTerm" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="1">{{ __('الفصل الأول') }}</option>
                            <option value="2">{{ __('الفصل الثاني') }}</option>
                            <option value="3">{{ __('الفصل الثالث') }}</option>
                            <option value="">{{ __('جلسة افتتاحية / سنوية') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('تاريخ وتوقيت الانعقاد:') }}</label>
                        <input type="datetime-local" wire:model="councilHeldAt" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('عنوان الجلسة أو الاجتماع:') }}</label>
                        <input type="text" wire:model="councilTitle" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('مكان الانعقاد:') }}</label>
                        <input type="text" wire:model="councilLocation" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الأستاذ المنسق (مقرر الجلسة):') }}</label>
                        <select wire:model="councilCoordinatorId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="">{{ __('اختيار الأستاذ المنسق') }}</option>
                            @foreach ($data['teachers'] as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('جدول الأعمال:') }}</label>
                        <textarea wire:model="councilAgenda" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('حصيلة تنفيذ المنهاج وتدرج التعلمات:') }}</label>
                        <textarea wire:model="councilPacingNotes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" placeholder="{{ __('ملاحظات تقدم الدروس، نسب الإنجاز، ومطابقة دفاتر النصوص...') }}"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('اتفاقيات الفروض الموحدة والمشاريع:') }}</label>
                        <textarea wire:model="councilAssessments" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" placeholder="{{ __('تواريخ ومواضيع الفروض الموحدة، سلم التنقيط، والمشاريع التربوية...') }}"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('التوصيات والقرارات المتفق عليها:') }}</label>
                        <textarea wire:model="councilRecommendations" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs" placeholder="{{ __('المعالجة البيداغوجية، حصص الدعم، تفعيل المخابر...') }}"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeCouncilModal" type="button" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded-lg">
                        {{ __('إلغاء') }}
                    </button>
                    <x-filament::button wire:click="saveCouncil" color="amber" size="sm">
                        {{ __('حفظ وتثبيت المحضر') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Create Seminar Modal --}}
    @if ($isSeminarModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-xl w-full p-6 border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        {{ __('برمجة ندوة تربوية مقاطعية جديدة') }}
                    </h3>
                    <button wire:click="closeSeminarModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('المادة:') }}</label>
                        <select wire:model="seminarSubjectId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="">{{ __('اختيار المادة') }}</option>
                            @foreach ($data['subjects'] as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('نوع الندوة:') }}</label>
                        <select wire:model="seminarType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="inspectoral_seminar">{{ __('ندوة تربوية مقاطعية') }}</option>
                            <option value="model_lesson">{{ __('درس نموذجي توضيحي') }}</option>
                            <option value="study_day">{{ __('يوم دراسي تكويني') }}</option>
                            <option value="exchange_visit">{{ __('زيارة بيداغوجية متبادلة') }}</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('موضوع الندوة / اليوم التكويني:') }}</label>
                        <input type="text" wire:model="seminarTitle" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" placeholder="{{ __('مثال: توظيف الكفاءات الختامية في تقويم التعلمات') }}" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('تاريخ الانعقاد:') }}</label>
                        <input type="date" wire:model="seminarDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('المؤسسة المستقبلة:') }}</label>
                        <input type="text" wire:model="seminarHostSchool" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('مفتش المادة المؤطر:') }}</label>
                        <input type="text" wire:model="seminarInspectorName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" placeholder="{{ __('اسم المفتش') }}" />
                    </div>

                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الأستاذ المنشط (إن وجد):') }}</label>
                        <select wire:model="seminarAnimatorId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs">
                            <option value="">{{ __('لا يوجد / إشراف المفتش') }}</option>
                            @foreach ($data['teachers'] as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('الفئة المستهدفة من الأساتذة:') }}</label>
                        <input type="text" wire:model="seminarTarget" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" placeholder="{{ __('مثال: أساتذة السنة الرابعة متوسط') }}" />
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('رقم الاستدعاء أو الإرسالية الرسمية:') }}</label>
                        <input type="text" wire:model="seminarCallNumber" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-2 text-xs" placeholder="{{ __('مثال: 2026/م.ت/412') }}" />
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-gray-300">{{ __('أهداف الندوة والمحاور البيداغوجية:') }}</label>
                        <textarea wire:model="seminarObjectives" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeSeminarModal" type="button" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded-lg">
                        {{ __('إلغاء') }}
                    </button>
                    <x-filament::button wire:click="saveSeminar" color="gray" size="sm">
                        {{ __('تسجيل الندوة') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
