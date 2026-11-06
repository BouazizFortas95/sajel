<x-filament-panels::page>

    @php
        $data = $this->getViewData();
    @endphp

    {{-- Top Header / Quick Action Strip --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xs">
        <div>
            <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                <span>🌱</span> {{ __('فضاء الحياة المدرسية والنوادي التربوية') }}
                <span class="text-xs font-mono font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2 py-0.5 rounded-full">
                    {{ $academicYear }}
                </span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('تفعيل الأنشطة الثقافية، العلمية، البيئية والرياضية لصقل مواهب التلاميذ وترسيخ قيم المواطنة') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($data['isAdmin'])
                <button
                    type="button"
                    wire:click="openNewClubModal"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5"
                >
                    <span>➕</span> {{ __('تأسيس نادٍ جديد') }}
                </button>
            @endif

            <a
                href="{{ route('print.school-life.annual-bilan', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                target="_blank"
                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold text-xs rounded-xl transition flex items-center gap-1"
                title="{{ __('معاينة الحصيلة السنوية للحياة المدرسية (A4 عرضي)') }}"
            >
                <span>🖨️</span> {{ __('تقرير الحصيلة السنوية') }}
            </a>

            <a
                href="{{ route('print.school-life.annual-bilan.pdf', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                target="_blank"
                class="px-2.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl transition"
                title="{{ __('تحميل الحصيلة السنوية بصيغة PDF') }}"
            >
                PDF
            </a>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-gray-900 dark:text-white font-mono block">{{ $data['total_clubs'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('إجمالي النوادي') }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono block">{{ $data['active_clubs'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('نوادٍ نشطة') }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono block">{{ $data['total_enrolled_students'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('تلميذ منخرط') }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono block">{{ $data['bureau_members_count'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('قادة المكاتب التنفيذية') }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono block">{{ $data['completed_activities'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('أنشطة منجزة') }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center shadow-xs">
            <span class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono block">{{ $data['total_attendees'] }}</span>
            <span class="text-[11px] font-bold text-gray-500">{{ __('مستفيد من الفعاليات') }}</span>
        </div>
    </div>

    {{-- Tabs Bar --}}
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 overflow-x-auto pb-2">
        <button
            type="button"
            wire:click="$set('activeTab', 'clubs')"
            class="px-4 py-2 text-xs font-bold rounded-lg transition whitespace-nowrap {{ $activeTab === 'clubs' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        >
            <span>🏰</span> {{ __('النوادي المدرسية المعتمدة') }} ({{ $data['total_clubs'] }})
        </button>

        <button
            type="button"
            wire:click="$set('activeTab', 'members')"
            class="px-4 py-2 text-xs font-bold rounded-lg transition whitespace-nowrap {{ $activeTab === 'members' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        >
            <span>👥</span> {{ __('عضوية ومكاتب النوادي والشهادات') }} ({{ $data['total_memberships'] }})
        </button>

        <button
            type="button"
            wire:click="$set('activeTab', 'activities')"
            class="px-4 py-2 text-xs font-bold rounded-lg transition whitespace-nowrap {{ $activeTab === 'activities' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        >
            <span>📅</span> {{ __('سجل الأنشطة والورشات') }} ({{ $data['total_activities'] }})
        </button>

        <button
            type="button"
            wire:click="$set('activeTab', 'bilan')"
            class="px-4 py-2 text-xs font-bold rounded-lg transition whitespace-nowrap {{ $activeTab === 'bilan' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        >
            <span>📊</span> {{ __('الحصيلة السنوية والمؤشرات') }}
        </button>
    </div>

    {{-- TAB 1: CLUBS GRID --}}
    @if ($activeTab === 'clubs')
        <div class="space-y-4">
            {{-- Category Filter Pills & Search --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <button
                        type="button"
                        wire:click="$set('selectedType', null)"
                        class="px-2.5 py-1 rounded-lg font-bold transition {{ is_null($selectedType) ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}"
                    >
                        {{ __('الكل') }} ({{ $data['total_clubs'] }})
                    </button>
                    @foreach ($data['clubs_by_type'] as $typeKey => $tInfo)
                        @if ($tInfo['count'] > 0)
                            <button
                                type="button"
                                wire:click="$set('selectedType', '{{ $typeKey }}')"
                                class="px-2.5 py-1 rounded-lg font-bold transition {{ $selectedType === $typeKey ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}"
                            >
                                {{ $tInfo['icon'] }} {{ $tInfo['label'] }} ({{ $tInfo['count'] }})
                            </button>
                        @endif
                    @endforeach
                </div>

                <div class="w-full md:w-64">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="{{ __('بحث عن نادٍ أو أستاذ مشرف...') }}"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>
            </div>

            {{-- Clubs Cards Grid --}}
            @if ($data['filteredClubs']->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($data['filteredClubs'] as $club)
                        @php
                            $activeCount = $club->activeMembersCount();
                            $capacityPct = $club->max_members > 0 ? round(($activeCount / $club->max_members) * 100) : 0;
                            $bureau = $club->bureauMembers();
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-emerald-400 dark:hover:border-emerald-600 transition">
                            <div class="space-y-2.5">
                                {{-- Card Header --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">{{ $club->type?->icon() }}</span>
                                        <div>
                                            <h3 class="text-sm font-black text-gray-900 dark:text-white leading-tight">
                                                {{ $club->name }}
                                            </h3>
                                            <span class="text-[10px] font-mono text-gray-500">{{ $club->code ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $club->type?->label() }}
                                    </span>
                                </div>

                                {{-- Supervisor Info --}}
                                <div class="bg-gray-50 dark:bg-gray-700/50 p-2.5 rounded-xl text-xs space-y-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">{{ __('الأستاذ المشرف') }}:</span>
                                        <strong class="text-gray-900 dark:text-white">{{ $club->leadTeacher?->name }}</strong>
                                    </div>
                                    @if ($club->assistantTeacher)
                                        <div class="flex justify-between items-center text-[11px]">
                                            <span class="text-gray-400">{{ __('المساعد') }}:</span>
                                            <span class="text-gray-600 dark:text-gray-300">{{ $club->assistantTeacher->name }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between items-center text-[11px] pt-1 border-t border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-500">{{ __('فضاء ويوم النشاط') }}:</span>
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $club->formattedMeetingSchedule() }}</span>
                                    </div>
                                </div>

                                {{-- Capacity Progress Bar --}}
                                <div>
                                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                                        <span class="text-gray-600 dark:text-gray-400">{{ __('انخراط التلاميذ') }}:</span>
                                        <span class="font-mono {{ $activeCount >= $club->max_members ? 'text-amber-600' : 'text-emerald-600' }}">
                                            {{ $activeCount }} / {{ $club->max_members }} {{ __('تلميذ') }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: {{ min($capacityPct, 100) }}%"></div>
                                    </div>
                                </div>

                                {{-- Executive Bureau Chip --}}
                                @if ($bureau->isNotEmpty())
                                    <div class="text-[11px] space-y-0.5 pt-1">
                                        <span class="text-gray-400 block text-[10px] font-bold">{{ __('المكتب التنفيذي:') }}</span>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($bureau as $bMember)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    {{ $bMember->role?->label() }}: {{ $bMember->student?->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Card Footer Actions --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-1.5">
                                <div class="flex items-center gap-1">
                                    <a
                                        href="{{ route('print.school-life.club-sheet', ['locale' => app()->getLocale(), 'club' => $club->id]) }}"
                                        target="_blank"
                                        class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-lg transition flex items-center gap-1"
                                        title="{{ __('معاينة وطباعة البطاقة الفنية للنادي') }}"
                                    >
                                        <span>🖨️</span> {{ __('البطاقة الفنية') }}
                                    </a>

                                    <a
                                        href="{{ route('print.school-life.club-sheet.pdf', ['locale' => app()->getLocale(), 'club' => $club->id]) }}"
                                        target="_blank"
                                        class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition"
                                        title="{{ __('تحميل البطاقة الفنية بصيغة PDF') }}"
                                    >
                                        PDF
                                    </a>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="openEnrollModal({{ $club->id }})"
                                        class="px-2 py-1 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950 rounded transition"
                                        title="{{ __('تسجيل تلميذ جديد في النادي') }}"
                                    >
                                        ➕ {{ __('انخراط') }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openActivityModal({{ $club->id }})"
                                        class="px-2 py-1 text-xs font-bold text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950 rounded transition"
                                        title="{{ __('توثيق نشاط أو ورشة للنادي') }}"
                                    >
                                        📝 {{ __('نشاط') }}
                                    </button>

                                    @if ($data['isAdmin'] || $club->lead_teacher_id === Auth::id())
                                        <button
                                            type="button"
                                            wire:click="openEditClubModal({{ $club->id }})"
                                            class="text-gray-400 hover:text-gray-600 text-xs font-bold px-1"
                                            title="{{ __('تعديل بيانات النادي') }}"
                                        >
                                            ✏️
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center text-gray-500">
                    <span class="text-4xl block mb-2">🌱</span>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('لم يتم تأسيس أو اعتماد نوادٍ مدرسية وفق هذا المعيار') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('يمكن للمدير تأسيس النوادي العلمية، البيئية والثقافية عبر زر الإضافة بأعلى الصفحة.') }}</p>
                </div>
            @endif
        </div>

    {{-- TAB 2: MEMBERSHIPS & CERTIFICATES --}}
    @elseif ($activeTab === 'members')
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700 gap-3">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ __('سجل انخراطات التلاميذ والمكاتب التنفيذية') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('توليد وطباعة شهادات التقدير والمشاركة السنوية لجميع المنخرطين في النوادي') }}</p>
                </div>

                {{-- Club Filter Dropdown --}}
                <div class="w-full md:w-64">
                    <select
                        wire:model.live="selectedClubId"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('جميع النوادي المدرسية') }}</option>
                        @foreach ($data['clubs'] as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->activeMembersCount() }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($data['memberships']->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-700">
                                <th class="p-2.5">#</th>
                                <th class="p-2.5">{{ __('اسم ولقب التلميذ') }}</th>
                                <th class="p-2.5">{{ __('القسم والفوج') }}</th>
                                <th class="p-2.5">{{ __('النادي المدرسي') }}</th>
                                <th class="p-2.5">{{ __('الصفة والمسؤولية') }}</th>
                                <th class="p-2.5">{{ __('تاريخ الانخراط') }}</th>
                                <th class="p-2.5 text-center">{{ __('شهادة التقدير والمشاركة') }}</th>
                                <th class="p-2.5 text-center">{{ __('إجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($data['memberships'] as $idx => $m)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-2.5 font-bold">{{ $loop->iteration }}</td>
                                    <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $m->student?->name }}</td>
                                    <td class="p-2.5 text-gray-600 dark:text-gray-300">{{ $m->student?->group?->classroom?->name ?? __('غير محدد') }}</td>
                                    <td class="p-2.5 font-medium text-emerald-700 dark:text-emerald-400">{{ $m->club?->name }}</td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $m->role->isBureau() ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $m->role?->label() }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 font-mono text-[11px]">{{ $m->joined_at?->format('d/m/Y') }}</td>
                                    <td class="p-2.5 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                href="{{ route('print.school-life.member-certificate', ['locale' => app()->getLocale(), 'membership' => $m->id]) }}"
                                                target="_blank"
                                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-[11px] font-bold rounded-lg transition flex items-center gap-1"
                                                title="{{ __('معاينة وطباعة شهادة التقدير الرسمية') }}"
                                            >
                                                <span>📜</span> {{ __('شهادة تقدير') }}
                                            </a>
                                            <a
                                                href="{{ route('print.school-life.member-certificate.pdf', ['locale' => app()->getLocale(), 'membership' => $m->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 text-[11px] font-bold rounded-lg transition"
                                                title="{{ __('تحميل الشهادة بصيغة PDF') }}"
                                            >
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                    <td class="p-2.5 text-center">
                                        @if ($data['isAdmin'] || $m->club?->lead_teacher_id === Auth::id())
                                            <button
                                                type="button"
                                                wire:click="deleteMembership({{ $m->id }})"
                                                wire:confirm="{{ __('هل أنت متأكد من إلغاء انخراط هذا التلميذ؟') }}"
                                                class="text-rose-600 hover:text-rose-800 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50"
                                                title="{{ __('إلغاء الانخراط') }}"
                                            >
                                                ✕
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-500">
                    <span class="text-3xl block mb-1">👥</span>
                    <p class="text-xs font-bold">{{ __('لا توجد انخراطات مسجلة حالياً.') }}</p>
                </div>
            @endif
        </div>

    {{-- TAB 3: ACTIVITIES LOGBOOK --}}
    @elseif ($activeTab === 'activities')
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700 gap-3">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ __('سجل الفعاليات، الورشات والخرجات الميدانية') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('توثيق الأنشطة التربوية والتحسيسية المنجزة من طرف نوادي المؤسسة') }}</p>
                </div>

                <div class="w-full md:w-64">
                    <select
                        wire:model.live="selectedClubId"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('جميع النوادي') }}</option>
                        @foreach ($data['clubs'] as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($data['activities']->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($data['activities'] as $act)
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ $act->activity_type?->icon() }}</span>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $act->title }}</h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                        {{ $act->activity_type?->label() }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $act->status->value === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $act->status->label() }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 flex flex-wrap items-center gap-3">
                                    <span>🏰 {{ $act->club?->name }}</span>
                                    <span>📅 {{ $act->activity_date?->format('d/m/Y') }} ({{ $act->formattedTime() }})</span>
                                    @if ($act->location)
                                        <span>📍 {{ $act->location }}</span>
                                    @endif
                                    @if ($act->attendees_count > 0)
                                        <span class="font-bold text-emerald-600">👥 {{ $act->attendees_count }} {{ __('مشارك') }}</span>
                                    @endif
                                </div>
                                @if ($act->summary)
                                    <p class="text-xs text-gray-600 dark:text-gray-300 pt-1 line-clamp-2">
                                        {{ $act->summary }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500">
                    <span class="text-3xl block mb-1">📅</span>
                    <p class="text-xs font-bold">{{ __('لا توجد أنشطة مسجلة لهذا الموسم.') }}</p>
                </div>
            @endif
        </div>

    {{-- TAB 4: ANNUAL BILAN & ANALYTICS --}}
    @elseif ($activeTab === 'bilan')
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Clubs by Category Card --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xs space-y-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🏷️</span> {{ __('توزيع النوادي حسب المجالات والتخصصات') }}
                    </h3>
                    <div class="space-y-2">
                        @foreach ($data['clubs_by_type'] as $t)
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <span class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                    <span>{{ $t['icon'] }}</span> {{ $t['label'] }}
                                </span>
                                <span class="font-mono font-bold px-2 py-0.5 rounded bg-white dark:bg-gray-800 border text-gray-900 dark:text-white">
                                    {{ $t['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Participation by Level Card --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xs space-y-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🎓</span> {{ __('انخراط التلاميذ حسب المستويات التعليمية') }}
                    </h3>
                    @if (!empty($data['level_distribution']))
                        <div class="space-y-2">
                            @foreach ($data['level_distribution'] as $lvlName => $count)
                                <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $lvlName }}</span>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ $count }} {{ __('منخرط') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-400 text-xs">
                            {{ __('لا تتوفر معطيات كافية لتوزيع المستويات.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Full Annual Report Printable Callout --}}
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-black text-emerald-900 dark:text-emerald-200">{{ __('طباعة الحصيلة السنوية الرسمية للحياة المدرسية والنوادي') }}</h4>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                        {{ __('إصدار التقرير السنوي الشامل المعد للعرض في مجلس التوجيه والتسيير ومفتشية التعليم المتوسط للإدارة (A4 عرضي)') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('print.school-life.annual-bilan', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                        target="_blank"
                        class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5"
                    >
                        <span>🖨️</span> {{ __('معاينة وطباعة التقرير كاملاً') }}
                    </a>
                    <a
                        href="{{ route('print.school-life.annual-bilan.pdf', ['locale' => app()->getLocale(), 'year' => $academicYear]) }}"
                        target="_blank"
                        class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition"
                    >
                        PDF
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODALS --}}
    {{-- ========================================================================= --}}

    {{-- MODAL 1: CREATE / EDIT CLUB --}}
    @if ($isClubModalOpen)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🏰</span> {{ $editingClubId ? __('تعديل بيانات النادي المدرسي') : __('تأسيس واعتماد نادٍ مدرسي جديد') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('تحديد الهوية، الأستاذ المشرف ومجال النشاط') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeClubModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('اسم النادي المدرسي:') }}
                        </label>
                        <input
                            type="text"
                            wire:model="clubName"
                            placeholder="{{ __('مثال: النادي الأخضر وحماية البيئة المدرسية') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('نوع وتصنيف النادي:') }}
                            </label>
                            <select
                                wire:model="clubType"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach (\App\Enums\ClubType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->icon() }} {{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('الأستاذ المنسق والمشرف:') }}
                            </label>
                            <select
                                wire:model="clubLeadTeacherId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach ($data['teachers'] as $tch)
                                    <option value="{{ $tch->id }}">{{ $tch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('يوم وتوقيت النشاط:') }}
                            </label>
                            <input
                                type="text"
                                wire:model="clubMeetingTime"
                                placeholder="{{ __('مثال: الثلاثاء 16:30 - 17:30') }}"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('فضاء النشاط بالمؤسسة:') }}
                            </label>
                            <input
                                type="text"
                                wire:model="clubMeetingLocation"
                                placeholder="{{ __('مخبر العلوم / قاعة الأنشطة') }}"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('أهداف النادي والكفاءات المستهدفة (اختياري):') }}
                        </label>
                        <textarea
                            wire:model="clubObjectives"
                            rows="2"
                            placeholder="{{ __('تنمية الحس البيئي، غرس قيم العمل التطوعي...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeClubModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>
                    <button
                        type="button"
                        wire:click="saveClub"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-xs transition"
                    >
                        {{ __('حفظ واعتماد النادي') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 2: ENROLL STUDENT --}}
    @if ($isEnrollModalOpen)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>➕</span> {{ __('تسجيل انخراط تلميذ بالنادي') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('إضافة التلميذ وتحديد رتبته ومسؤوليته في النادي') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeEnrollModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('اختر التلميذ:') }}
                        </label>
                        <select
                            wire:model="enrollingStudentId"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">{{ __('— حدد التلميذ من القائمة —') }}</option>
                            @foreach ($data['students'] as $st)
                                <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->group?->classroom?->name ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('الصفة والمسؤولية في النادي:') }}
                        </label>
                        <select
                            wire:model="enrollingRole"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            @foreach (\App\Enums\ClubMemberRole::cases() as $r)
                                <option value="{{ $r->value }}">{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('ملاحظات أو مواهب خاصة (اختياري):') }}
                        </label>
                        <input
                            type="text"
                            wire:model="enrollingNotes"
                            placeholder="{{ __('مثال: إتقان الرسم، مهارة الإلقاء...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeEnrollModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>
                    <button
                        type="button"
                        wire:click="confirmEnrollStudent"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs transition"
                    >
                        {{ __('تأكيد الانخراط') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 3: RECORD ACTIVITY --}}
    @if ($isActivityModalOpen)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📝</span> {{ __('توثيق فعالية أو ورشة عمل') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('تسجيل النشاط المنجز وعدد المشاركين وحصيلة العمل') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeActivityModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('عنوان الفعالية / النشاط:') }}
                        </label>
                        <input
                            type="text"
                            wire:model="activityTitle"
                            placeholder="{{ __('مثال: حملة التشجير الكبرى بفناء المؤسسة') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('نوع النشاط:') }}
                            </label>
                            <select
                                wire:model="activityType"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach (\App\Enums\ClubActivityType::cases() as $actType)
                                    <option value="{{ $actType->value }}">{{ $actType->icon() }} {{ $actType->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('تاريخ الإنجاز:') }}
                            </label>
                            <input
                                type="date"
                                wire:model="activityDate"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('عدد التلاميذ المشاركين:') }}
                            </label>
                            <input
                                type="number"
                                min="0"
                                wire:model="activityAttendeesCount"
                                class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                                {{ __('مكان النشاط:') }}
                            </label>
                            <input
                                type="text"
                                wire:model="activityLocation"
                                placeholder="{{ __('فناء المؤسسة / المكتبة') }}"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">
                            {{ __('ملخص النتائج وحصيلة الفعالية:') }}
                        </label>
                        <textarea
                            wire:model="activitySummary"
                            rows="2"
                            placeholder="{{ __('تم غرس 50 شتلة بمشاركة أعضاء النادي...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closeActivityModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        {{ __('إلغاء') }}
                    </button>
                    <button
                        type="button"
                        wire:click="confirmRecordActivity"
                        class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-lg shadow-xs transition"
                    >
                        {{ __('حفظ وتوثيق النشاط') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>

